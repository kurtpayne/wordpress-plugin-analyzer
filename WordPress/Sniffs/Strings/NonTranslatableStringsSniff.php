<?php
/**
 * Look for strings that aren't translatable as part of __().
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Kurt Payne <kpayne@godaddy.com>
 * @copyright 2011 GoDaddy.com
 * @link      https://github.com/kurtpayne/wordpress-codesniffer
 */

/**
 * Look for strings that aren't translatable as part of __().
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Kurt Payne <kpayne@godaddy.com>
 * @copyright 2011 GoDaddy.com
 * @link      https://github.com/kurtpayne/wordpress-codesniffer
 */
class WordPress_Sniffs_Strings_NonTranslatableStringsSniff implements PHP_CodeSniffer_Sniff
{
    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(
                T_CONSTANT_ENCAPSED_STRING,
                T_DOUBLE_QUOTED_STRING,
               );

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
        
        // Get whatever bordered the string on the left
        $left = '';
        for ($i = $stackPtr - 1 ; $i >= 0 ; $i--) {
            $left = $tokens[$i]['content'] . $left;

            // Look for anything else that would let us continue parsing rightward
            if (in_array($tokens[$i]['code'], array(T_STRING_CONCAT, T_OPEN_PARENTHESIS, T_WHITESPACE, T_OPEN_SQUARE_BRACKET, T_VARIABLE))) {
                break;
            }
        }

        // Get whatever bordered the string on the right
        $right = '';
        for ($i = $stackPtr + 1 ; $i < count($tokens) ; $i++) {
            $right .= $tokens[$i]['content'];

            // Look for anything that would require us to stop parsing
            if (in_array($tokens[$i]['code'], array(T_DOUBLE_ARROW, T_SEMICOLON))) {
                break;
            }
            
            // Look for anything else that would let us continue parsing rightward
            elseif (!in_array($tokens[$i]['code'], array(T_STRING_CONCAT, T_CLOSE_PARENTHESIS, T_WHITESPACE, T_CLOSE_SQUARE_BRACKET, T_VARIABLE))) {
                break;
            }            
        }
        
        // Look for balanced parentheses and __( in the $left and ) in the right
        if (strlen($left) && strlen($right)) {
            $balancedParens = (substr_count('(', $left) == substr_count(')', $right));
            $xFuncFound = (FALSE !== strpos($left, '__(') && FALSE !== strpos($right, ')'));
        } else {
            $balancedParens = false;
            $xFuncFound = false;
        }
        $emptyString = ('' == trim($tokens[$stackPtr]['content']));
        $arrayKey = ($left == '[' || $right == ']' || '=>' == substr(trim($right), -2));
        
        if ((!$balancedParens || !$xFuncFound) && !$emptyString && !$arrayKey) {
            $error = 'String ' . $tokens[$stackPtr]['content'] . ' may not be ready for translation, try to use __x() instead';
            $phpcsFile->addWarning($error, $stackPtr);
        }

    }//end process()


}//end class

?>
