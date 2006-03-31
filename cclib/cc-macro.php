<?
/*
* Creative Commons has made the contents of this file
* available under a CC-GNU-GPL license:
*
* http://creativecommons.org/licenses/GPL/2.0/
*
* A copy of the full license can be found as part of this
* distribution in the file LICENSE.TXT.
* 
* You may use the ccHost software in accordance with the
* terms of that license. You agree that you are solely 
* responsible for your use of the ccHost software and you
* represent and warrant to Creative Commons that your use
* of the ccHost software will comply with the CC-GNU-GPL.
*
* $Header$
*
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_GET_MACROS,   array( 'CCMacro' , 'OnGetMacros'));

class CCMacro
{
    /**
    * Event handler for getting renaming/id3 tagging macros
    *
    * @param array $dummy Record we're getting macros for (if null returns documentation)
    * @param array $dummy1 (not used)
    * @param array $patterns Substituion pattern to be used when renaming/tagging
    * @param array $dummy2 (not used)
    */
    function OnGetMacros(&$dummy, &$dummy1, &$patterns, &$dummy2)
    {
        if( empty($dummy) )
        {
            $patterns['%%'] = 'Percent sign (%)';
            $patterns['%Y%'] = 'Current year (' . date('Y') . ')';
            $patterns['%d%'] = 'Current day (' . date('d') . ')';
            $patterns['%m%'] = 'Current month (' . date('m') . ')';
        }
    }

    /**
    * Compiles (expands) macros in mask into string
    *
    * @param array $patterns Macro patters and their values
    * @param string $mask String containing macros to expand
    * @param bool $replace_sp Set to true to place spaces with '_'
    * @returns string $expanded Expanded/compiled string
    */
    function TranslateMask($patterns,$mask,$replace_sp = false)
    {
        $patterns['%%']  = '%';
        $patterns['%Y%'] = date('Y');
        $patterns['%d%'] = date('d');
        $patterns['%m%'] = date('m');

        $regex = array();
        $replacesments = array();
        foreach( $patterns as $r => $repl )
        {
            $regex[] = '/' . $r . '/';
            $replacements[] = $repl;
        }
        
        $result = preg_replace( $regex, $replacements, $mask );

        if( $replace_sp )
            $result = str_replace(' ','_',$result);

        return($result);
    }
}
?>