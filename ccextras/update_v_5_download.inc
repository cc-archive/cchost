<?
if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

class v_5_download extends CCUpdate
{
    function Update()
    {
        $this->_update_download();

        CCPage::Prompt(_('Download extensions installed'));
    }

    function _update_download()
    {
        $this->_check_for_field('cc_tbl_files', 'file_num_download',
                        'INT(7) unsigned NOT NULL default 0');
    }
}
?>
