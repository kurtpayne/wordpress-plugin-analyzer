<?php
/**
 * Generic_Sniffs_Formatting_MultipleStatementAlignmentSniff.
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Marc McIntyre <mmcintyre@squiz.net>
 * @copyright 2006 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   http://matrix.squiz.net/developer/tools/php_cs/licence BSD Licence
 * @version   CVS: $Id: MultipleStatementAlignmentSniff.php,v 1.23 2008/12/02 02:38:34 squiz Exp $
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Generic_Sniffs_Formatting_MultipleStatementAlignmentSniff.
 *
 * Checks alignment of assignments. If there are multiple adjacent assignments,
 * it will check that the equals signs of each assignment are aligned. It will
 * display a warning to advise that the signs should be aligned.
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Marc McIntyre <mmcintyre@squiz.net>
 * @copyright 2006 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   http://matrix.squiz.net/developer/tools/php_cs/licence BSD Licence
 * @version   Release: 1.2.0RC1
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class WordPress_Sniffs_Formatting_MultipleStatementAlignmentSniff implements PHP_CodeSniffer_Sniff
{

    /**
     * A list of tokenizers this sniff supports.
     *
     * @var array
     */
    public $supportedTokenizers = array(
                                   'PHP',
                                   'JS',
                                  );

    /**
     * If true, an error will be thrown; otherwise a warning.
     *
     * @var bool
     */
    protected $error = false;

    /**
     * The maximum amount of padding before the alignment is ignored.
     *
     * If the amount of padding required to align this assignment with the
     * surrounding assignments exceeds this number, the assignment will be
     * ignored and no errors or warnings will be thrown.
     *
     * @var int
     */
    protected $maxPadding = 1000;

    /**
     * If true, multi-line assignments are not checked.
     *
     * @var int
     */
    protected $ignoreMultiLine = false;


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return PHP_CodeSniffer_Tokens::$assignmentTokens;

    }//end register()


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token
     *                                        in the stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        // Ignore assignments used in a condition, like an IF or FOR.
        if (isset($tokens[$stackPtr]['nested_parenthesis']) === true) {
            foreach ($tokens[$stackPtr]['nested_parenthesis'] as $start => $end) {
                if (isset($tokens[$start]['parenthesis_owner']) === true) {
                    return;
                }
            }
        }

        /*
            By this stage, it is known that there is an assignment on this line.
            We only want to process the block once we reach the last assignment,
            so we need to determine if there are more to follow.
        */

        // The assignment may span over multiple lines, so look for the
        // end of the assignment so we can check assignment blocks correctly.
        $lineEnd = $phpcsFile->findNext(T_SEMICOLON, ($stackPtr + 1));

        $nextAssign = $phpcsFile->findNext(
            PHP_CodeSniffer_Tokens::$assignmentTokens,
            ($lineEnd + 1)
        );

        if ($nextAssign !== false) {
            $isAssign = true;
            if ($tokens[$nextAssign]['line'] === ($tokens[$lineEnd]['line'] + 1)) {
                // Assignment may be in the same block as this one. Just make sure
                // it is not used in a condition, like an IF or FOR.
                if (isset($tokens[$nextAssign]['nested_parenthesis']) === true) {
                    foreach ($tokens[$nextAssign]['nested_parenthesis'] as $start => $end) {
                        if (isset($tokens[$start]['parenthesis_owner']) === true) {
                            // Not an assignment.
                            $isAssign = false;
                            break;
                        }
                    }
                }

                if ($isAssign === true) {
                    return;
                }
            }
        }

        // Getting here means that this is the last in a block of statements.
        $assignments    = array();
        $assignments[]  = $stackPtr;
        $prevAssignment = $stackPtr;
        $lastLine       = $tokens[$stackPtr]['line'];

        while (($prevAssignment = $phpcsFile->findPrevious(PHP_CodeSniffer_Tokens::$assignmentTokens, ($prevAssignment - 1))) !== false) {

            // The assignment's end token must be on the line directly
            // above the current one to be in the same assignment block.
            $lineEnd = $phpcsFile->findNext(T_SEMICOLON, ($prevAssignment + 1));

            // And the end token must actually belong to this assignment.
            $nextOpener = $phpcsFile->findNext(
                PHP_CodeSniffer_Tokens::$scopeOpeners,
                ($prevAssignment + 1)
            );

            if ($nextOpener !== false && $nextOpener < $lineEnd) {
                break;
            }

            if ($tokens[$lineEnd]['line'] !== ($lastLine - 1)) {
                break;
            }

            // Make sure it is not assigned inside a condition (eg. IF, FOR).
            if (isset($tokens[$prevAssignment]['nested_parenthesis']) === true) {
                foreach ($tokens[$prevAssignment]['nested_parenthesis'] as $start => $end) {
                    if (isset($tokens[$start]['parenthesis_owner']) === true) {
                        break(2);
                    }
                }
            }

            $assignments[] = $prevAssignment;
            $lastLine      = $tokens[$prevAssignment]['line'];
        }//end while

        $assignmentData      = array();
        $maxAssignmentLength = 0;
        $maxVariableLength   = 0;

        foreach ($assignments as $assignment) {
            $prev = $phpcsFile->findPrevious(
                PHP_CodeSniffer_Tokens::$emptyTokens,
                ($assignment - 1),
                null,
                true
            );

            $endColumn = $tokens[($prev + 1)]['column'];

            if ($maxVariableLength < $endColumn) {
                $maxVariableLength = $endColumn;
            }

            if ($maxAssignmentLength < strlen($tokens[$assignment]['content'])) {
                $maxAssignmentLength = strlen($tokens[$assignment]['content']);
            }

            $assignmentData[$assignment]
                = array(
                   'variable_length'   => $endColumn,
                   'assignment_length' => strlen($tokens[$assignment]['content']),
                  );
        }//end foreach

        foreach ($assignmentData as $assignment => $data) {
            if ($data['assignment_length'] === $maxAssignmentLength) {
                if ($data['variable_length'] === $maxVariableLength) {
                    // The assignment is the longest possible, so the column that
                    // everything has to align to is based on it.
                    $column = ($maxVariableLength + 1);
                    break;
                } else {
                    // The assignment token is the longest out of all of the
                    // assignments, but the variable name is not, so the column
                    // the start at can go back more to cover the space
                    // between the variable name and the assigment operator.
                    $column = ($maxVariableLength - ($maxAssignmentLength - 1) + 1);
                }
            }
        }

        // Determine the actual position that each equals sign should be in.
        foreach ($assignments as $assignment) {
            // Actual column takes into account the length of the assignment operator.
            $actualColumn = ($column + $maxAssignmentLength - strlen($tokens[$assignment]['content']));
            if ($tokens[$assignment]['column'] !== $actualColumn) {
                $prev = $phpcsFile->findPrevious(
                    PHP_CodeSniffer_Tokens::$emptyTokens,
                    ($assignment - 1),
                    null,
                    true
                );

                $expected = ($actualColumn - $tokens[($prev + 1)]['column']);

                if ($tokens[$assignment]['line'] !== $tokens[$prev]['line']) {
                    // Instead of working out how many spaces there are
                    // across new lines, the error message becomes more
                    // generic below.
                    $found = null;
                } else {
                    $found = ($tokens[$assignment]['column'] - $tokens[($prev + 1)]['column']);
                }

                // If the expected number of spaces for alignment exceeds the
                // maxPadding rule, we can ignore this assignment.
                if ($expected > $this->maxPadding) {
                    continue;
                }

                // Skip multi-line assignments if required.
                if ($found === null && $this->ignoreMultiLine === true) {
                    continue;
                }

                $expected .= ($expected === 1) ? ' space' : ' spaces';
                if ($found === null) {
                    $found = 'a new line';
                } else {
                    $found .= ($found === 1) ? ' space' : ' spaces';
                }

                if (count($assignments) === 1) {
                    $error = "Equals sign not aligned correctly; expected $expected but found $found";
                } else if ($expected < 6) {
                    $error = "Equals sign not aligned with surrounding assignments; expected $expected but found $found";
                }
				else
					$error = false;

				if ($error !== false)
				{
					if ($this->error === true) {
						$phpcsFile->addError($error, $assignment);
					} else {
						$phpcsFile->addWarning($error, $assignment);
					}
				}
            }//end if
        }//end foreach

    }//end process()


}//end class

?>