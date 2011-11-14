<?php

/**
 * Enforces WordPress function call format, based upon Squiz code
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    John Godley <john@urbangiraffe.com>
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Marc McIntyre <mmcintyre@squiz.net>
 */

/**
 * Enforces WordPress array format
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    John Godley <john@urbangiraffe.com>
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Marc McIntyre <mmcintyre@squiz.net>
 */
class WordPress_Sniffs_Functions_FunctionCallSignatureSniff implements PHP_CodeSniffer_Sniff
{
	private $type;

	/**
	 * The space equivalent of tabs (4 spaces = 1 tab)
	 * This is used to ensure accurate counting when lining up
	 * multi-line function definitions
	 * @var string
	 */
	const SPACED_TAB = '    ';

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(T_STRING);

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

        // Find the next non-empty token.
        $openBracket = $phpcsFile->findNext(PHP_CodeSniffer_Tokens::$emptyTokens, ($stackPtr + 1), null, true);

        if ($tokens[$openBracket]['code'] !== T_OPEN_PARENTHESIS) {
            // Not a function call.
            return;
        }

        if (isset($tokens[$openBracket]['parenthesis_closer']) === false) {
            // Not a function call.
            return;
        }
		
        // Find the previous non-empty token.
        $previous = $phpcsFile->findPrevious(PHP_CodeSniffer_Tokens::$emptyTokens, ($stackPtr - 1), null, true);
        if ($tokens[$previous]['code'] === T_FUNCTION)
			$this->type = 'definition';

/*        if ($tokens[$previous]['code'] === T_NEW) {
            // We are creating an object, not calling a function.
            return;
        }
		*/
        $closeBracket = $tokens[$openBracket]['parenthesis_closer'];

        if (($stackPtr + 1) !== $openBracket) {
            // Checking this: $value = my_function[*](...).
            $error = 'Space before opening parenthesis of function '.$this->type.' prohibited';
            $phpcsFile->addError($error, $stackPtr);
        }

        $next = $phpcsFile->findNext(T_WHITESPACE, ($closeBracket + 1), null, true);
        if ($tokens[$next]['code'] === T_SEMICOLON) {
            if (in_array($tokens[($closeBracket + 1)]['code'], PHP_CodeSniffer_Tokens::$emptyTokens) === true) {
                $error = 'Space after closing parenthesis of function '.$this->type.' prohibited';
                $phpcsFile->addError($error, $closeBracket);
            }
        }

        // Check if this is a single line or multi-line function call.
        if ($tokens[$openBracket]['line'] === $tokens[$closeBracket]['line']) {
            $this->processSingleLineCall($phpcsFile, $stackPtr, $openBracket, $tokens);
        } else {
            $this->processMultiLineCall($phpcsFile, $stackPtr, $openBracket, $tokens);
        }

    }//end process()


    /**
     * Processes single-line calls.
     *
     * @param PHP_CodeSniffer_File $phpcsFile   The file being scanned.
     * @param int                  $stackPtr    The position of the current token
     *                                          in the stack passed in $tokens.
     * @param int                  $openBracket The position of the openning bracket
     *                                          in the stack passed in $tokens.
     * @param array                $tokens      The stack of tokens that make up
     *                                          the file.
     *
     * @return void
     */
    public function processSingleLineCall(PHP_CodeSniffer_File $phpcsFile, $stackPtr, $openBracket, $tokens)
    {
        if ($tokens[($openBracket + 1)]['code'] !== T_WHITESPACE && $tokens[($openBracket + 1)]['code'] !== T_CLOSE_PARENTHESIS) {
            // Checking this: $value = my_function([*]...).
            $error = 'No space after opening parenthesis of function '.$this->type.' prohibited';
            $phpcsFile->addError($error, $stackPtr);
        }

        $closer = $tokens[$openBracket]['parenthesis_closer'];

        if ($tokens[($closer - 1)]['code'] !== T_WHITESPACE) {
            // Checking this: $value = my_function(...[*]).
            $between = $phpcsFile->findNext(T_WHITESPACE, ($openBracket + 1), null, true);

            // Only throw an error if there is some content between the parenthesis.
            // i.e., Checking for this: $value = my_function().
            // If there is no content, then we would have thrown an error in the
            // previous IF statement because it would look like this:
            // $value = my_function( ).

            if ($between !== $closer) {
                $error = 'No space before closing parenthesis of function '.$this->type.' prohibited';
                $phpcsFile->addError($error, $closer);
            }
        }

    }//end processSingleLineCall()


    /**
     * Processes multi-line calls.
     *
     * @param PHP_CodeSniffer_File $phpcsFile   The file being scanned.
     * @param int                  $stackPtr    The position of the current token
     *                                          in the stack passed in $tokens.
     * @param int                  $openBracket The position of the openning bracket
     *                                          in the stack passed in $tokens.
     * @param array                $tokens      The stack of tokens that make up
     *                                          the file.
     *
     * @return void
     */
    public function processMultiLineCall(PHP_CodeSniffer_File $phpcsFile, $stackPtr, $openBracket, $tokens)
    {
        // We need to work out how far indented the function
        // call itself is, so we can work out how far to
        // indent the arguments.
        $functionIndent = 0;
        for ($i = ($stackPtr - 1); $i >= 0; $i--) {
            if ($tokens[$i]['line'] !== $tokens[$stackPtr]['line']) {
                $i++;
                break;
            }
        }

        if ($tokens[$i]['code'] === T_WHITESPACE) {
            $functionIndent = strlen($tokens[$i]['content']);
        }

        // Each line between the parenthesis should be indented 4 spaces.
        $closeBracket = $tokens[$openBracket]['parenthesis_closer'];
        $lastLine     = $tokens[$openBracket]['line'];
        for ($i = ($openBracket + 1); $i < $closeBracket; $i++) {
            // Skip nested function calls.
            if ($tokens[$i]['code'] === T_OPEN_PARENTHESIS) {
                $i        = $tokens[$i]['parenthesis_closer'];
                $lastLine = $tokens[$i]['line'];
                continue;
            }

            if ($tokens[$i]['line'] !== $lastLine) {
                $lastLine = $tokens[$i]['line'];

                // We changed lines, so this should be a whitespace indent token.
                if (in_array($tokens[$i]['code'], PHP_CodeSniffer_Tokens::$heredocTokens) === true) {
                    // Ignore heredoc indentation.
                    continue;
                }

                if (in_array($tokens[$i]['code'], PHP_CodeSniffer_Tokens::$stringTokens) === true) {
                    if ($tokens[$i]['code'] === $tokens[($i - 1)]['code']) {
                        // Ignore multi-line string indentation.
                        continue;
                    }
                }

                if ($tokens[$i]['line'] === $tokens[$closeBracket]['line']) {
                    // Closing brace needs to be indented to the same level
                    // as the function call.
                    $expectedIndent = $functionIndent;
                } else {
                    $expectedIndent = ($functionIndent + 4);
                }

                if ($tokens[$i]['code'] !== T_WHITESPACE) {
                    $foundIndent = 0;
                } else {
                    $foundIndent = strlen(str_replace("\t", $this::SPACED_TAB, $tokens[$i]['content']));
                }

                if ($expectedIndent !== $foundIndent) {
                    $error = "Multi-line function ".$this->type." not indented correctly; expected $expectedIndent spaces but found $foundIndent";
                    $phpcsFile->addError($error, $i);
                }
            }//end if
        }//end for

        if ($tokens[($openBracket + 1)]['content'] !== $phpcsFile->eolChar) {
            $error = 'Opening parenthesis of a multi-line function call must be the last content on the line';
            $phpcsFile->addError($error, $stackPtr);
        }

        $prev = $phpcsFile->findPrevious(T_WHITESPACE, ($closeBracket - 1), null, true);
        if ($tokens[$prev]['line'] === $tokens[$closeBracket]['line']) {
            $error = 'Closing parenthesis of a multi-line function '.$this->type.' must be on a line by itself';
            $phpcsFile->addError($error, $closeBracket);
        }

    }//end processMultiLineCall()


}//end class
?>