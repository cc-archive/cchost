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
* $Id$
*
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCevents::AddHandler( CC_EVENT_MAP_URLS, array( 'MixterMailList', 'OnMapUrls' ) );

class MxMailListForm extends CCForm
{
    function MxMailListForm($contest='')
    {
        $this->CCForm();

        $fields = array(
             'tags' => array(
                 'label' => 'Tags',
                 'formatter' => 'tagsedit',
                 'value' => 'contest_entry,fortminor',
                 'flags' => CCFF_REQUIRED,
                 ),
             'mail_subject' => array(
                 'label' => 'Subject',
                 'formatter' => 'textedit',
                 'flags' => CCFF_REQUIRED,
                 ),
             'mail_body' => array(
                 'label' => 'Text',
                 'formatter' => 'textarea',
                 'flags' => CCFF_NONE,
                 ),
         );
      
        $this->AddFormFields($fields);
    }
}

class MixterMailListTable extends CCUploads
{
    function MixterMailListTable()
    {
        $this->CCUploads();
        $this->_key_field = 'upload_user';
    }

    function & GetTable()
    {
        static $_table;
        if( !isset($_table) )
            $_table = new MixterMailListTable();
        return $_table;
    }
}

class MixterMailList
{
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('listmail'), array( 'MixterMailList', 'ListMail' ), CC_ADMIN_ONLY );
    }

    function ListMail()
    {
        CCPage::SetTitle('List Mailer [BETA]');
        $form = new MxMailListForm();

        if( empty($_POST['maillist']) || !$form->ValidateFields() )
        {
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            $form->GetFormValues($fields);
            $uploads =& MixterMailListTable::GetTable();
            $uploads->SetTagFilter($fields['tags'],'all');
            $uploads->GroupOnKey(true);
            $uploads->SetOrder('user_email','ASC');
            $rows = $uploads->QueryRows('','user_email');

            $mailer = new CCMailer();
            $from = $mailer->DefaultFrom();
            $mailer->From( $from );
            $mailer->Subject( $fields['mail_subject'] );
            $mailer->Body( $fields['mail_body'] );

            $count = 0;
            foreach( $rows as $row )
            {
                $mailer->To( $row['user_email'] );
                $ok = $mailer->Send();
                if( !$ok )
                    break;
                $count++;
            }

            CCPage::Prompt("$count email sent OK");
            if( !$ok )
                CCPage::Prompt("sending mail failed");
        }
    }
}

?>