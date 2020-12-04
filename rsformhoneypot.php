<?php
/**
 * @package       RSForm!Pro
 * @copyright (C) 2020 www.rsjoomla.com
 * @license       GPL, http://www.gnu.org/copyleft/gpl.html
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

class plgSystemRsformhoneypot extends JPlugin
{
    protected $autoloadLanguage = true;

    public function rsfp_f_onBeforeFormProcess($args)
    {
        $formId = $args['post']['formId'];
        $data   = $this->getData($formId);

        if ((int) $data->HoneypotState === 1)
        {
            // https://webaim.org/blog/spam_free_accessible_forms/
            $spam = false;

            // Detect form elements for the most common header injections and other code
            if (preg_match("/bcc:|cc:|multipart|\[url|Content-Type:/i", implode($_POST['form'])))
            {
                $spam = true;
            }

            // Detect more than 3 outgoing links
            if (preg_match_all("/<a|https?:/i", implode($_POST['form']), $out) > 3)
            {
                $spam = true;
            }

            // Detect content within a hidden form element
            if (!empty($_POST['form'][ucfirst($data->HoneypotName)]))
            {
                $spam = true;
            }

//			// Ensure the form is posted from your server
//			if ((isset($_SERVER['HTTP_REFERER']) && stristr($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST'])))
//			{
//				$spam = true;
//			}

            // Sent spammer to somewhere else
            if ($spam)
            {
                header('Location: ' . $data->HoneypotUrl);
                die;
            }
        }
    }

    public function str_replace_first($from, $to, $content)
    {
        $from = '/' . preg_quote($from, '/') . '/';

        return preg_replace($from, $to, $content, 1);
    }

    public function rsfp_f_onBeforeFormDisplay($args)
    {
        $formId         = $args['formId'];
        $formLayout     = &$args['formLayout'];
        $formLayoutName = &$args['formLayoutName'];
        $data           = $this->getData($formId);

        if ($data->HoneypotState !== '1' || empty($data->HoneypotName))
        {
            return;
        }

        $style    = '.rsform-block-' . $data->HoneypotName . ' {display:none!important;visibility:hidden;}';
        $newField = $this->getNewfield($formLayoutName, $data->HoneypotName);

        $formLayout = self::str_replace_first('formSpan12">', 'formSpan12">' . "\n" . $newField, $formLayout);
        $formLayout = self::str_replace_first('columns">', 'columns">' . "\n" . $newField, $formLayout);

        RSFormProAssets::addStyleDeclaration($style);
    }

    public function getNewField($formLayoutName, $HoneypotName)
    {
        switch ($formLayoutName)
        {
            case 'responsive':
                $newField = "
			<div class=\"rsform-block rsform-block-" . $HoneypotName . "\">\n"
                    . "				<label class=\"formControlLabel\" for=\"" . ucfirst($HoneypotName) . "\">" . ucfirst($HoneypotName) . "</label>\n"
                    . "				<div class=\"formControls\">\n"
                    . "					<div class=\"formBody\">\n"
                    . "						<input type=\"text\" value=\"\" size=\"20\" name=\"form[" . ucfirst($HoneypotName) . "]\" id=\"" . ucfirst($HoneypotName) . "\" class=\"rsform-input-box\">\n"
                    . "					</div>\n"
                    . "				</div>\n"
                    . "			</div>\n";
                break;

            case 'foundation':
                $newField = '
			<div class="row rsform-block rsform-block-' . $HoneypotName . '">'
                    . '<div class="medium-3 columns">'
                    . '<label class="formControlLabel has-tip" data-tooltip="" aria-haspopup="true" data-disable-hover="false" tabindex="1" title="" for="' . ucfirst($HoneypotName) . '">'
                    . ucfirst($HoneypotName) . '</label>'
                    . '</div>'
                    . '<div class="medium-9 columns formControls">'
                    . '<input type="text" value="" size="20" name="form[' . ucfirst($HoneypotName) . ']" id="' . ucfirst($HoneypotName) . '" class="rsform-input-box" />'
                    . '</div>'
                    . '</div>';
                break;

            default:
                $newField = '';
        }

        return $newField;
    }

    public function rsfp_bk_onAfterShowFormScriptsTabsTab()
    {
        echo '<li><a href="javascript: void(0);" id="honeypot"><span class="rsficon rsficon-code"></span><span class="inner-text">Honeypot</span></a></li>';
    }

    public function rsfp_bk_onAfterShowFormScriptsTabs()
    {
        $formId = (int) Factory::$application->input->get('formId');
        $data   = $this->getData($formId);

        $check1 = ((int) $data->HoneypotState === 1) ? 'selected="selected"' : '';
        $check0 = (empty($data->HoneypotState) || $data->HoneypotState === 0) ? 'selected="selected"' : '';

        $enabledText = Text::_('JENABLED');
        $yesText     = Text::_('JYES');
        $noText      = Text::_('JNO');
        $nameText    = Text::_('JFIELD_NAME_LABEL');

        echo <<<EOD
<div id="honeypot">
	<fieldset>
		<h3 class="rsfp-legend">Honeypot</h3>
		<table class="admintable" width="100%">
			<tr>
				<td width="25%" align="right" nowrap="nowrap" class="key">$enabledText</td>
				<td>
					<fieldset id="HoneypotState" name="HoneypotState" class="btn-group radio">
						<select id="HoneypotState" name="HoneypotState">
							<option value="0" $check0>$noText</option>
							<option value="1" $check1>$yesText</option>
						</select>
					</fieldset>
				</td>
			</tr>
			<tr>
				<td colspan="2">&nbsp</td>
			</tr>
			<tr>
				<td width="25%" align="right" nowrap="nowrap" class="key">$nameText</td>
				<td><input name="HoneypotName" class="rs_inp rs_80" value="$data->HoneypotName" size="105" id="HoneypotName" /></td>
			</tr>
			<tr>
				<td colspan="2">&nbsp;</td>
			</tr>
			<tr>
				<td width="25%" align="right" nowrap="nowrap" class="key">URL</td>
				<td><input name="HoneypotUrl" class="rs_inp rs_80" value="$data->HoneypotUrl" size="105" id="HoneypotUrl" /></td>
			</tr>
		</table>
	</fieldset>
</div>
EOD;
    }

    function getData($formId)
    {
        $db    = Factory::getDbo();
        $query = $db->getQuery(true)
            ->select(
                $db->quoteName(
                    [
                        'f.HoneypotName',
                        'f.HoneypotUrl',
                        'f.HoneypotState'
                    ]
                )
            )
            ->from(
                $db->quoteName('#__rsform_forms', 'f')
            )
            ->where(
                $db->quoteName('f.FormId') . ' = ' . (int) $formId
            );

        $db->setQuery($query);

        return $db->loadObject();
    }
}
