<?php
/**
 * @package    Joomla.JEDChecker
 *
 * @copyright  Copyright (C) 2017 - 2021 Open Source Matters, Inc. All rights reserved.
 * 			   Copyright (C) 2008 - 2016 fasterjoomla.com. All rights reserved.
 * @author     Riccardo Zorn <support@fasterjoomla.com>
 * 			   Bernard Toplak <bernard@orion-web.hr>
 *
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die('Restricted access');

// Include the rule base class
require_once JPATH_COMPONENT_ADMINISTRATOR . '/models/rule.php';

// Include the helper class
require_once JPATH_COMPONENT_ADMINISTRATOR . '/libraries/helper.php';

/**
 * JedcheckerRulesJamss
 *
 * @since  2014-02-23
 * Attempts to identify deprecated code, unsafe code, leftover stuff
 */
class JedcheckerRulesJamss extends JEDcheckerRule
{
	/**
	 * The formal ID of this rule. For example: SE1.
	 *
	 * @var    string
	 */
	protected $id = 'Jamss';

	/**
	 * The title or caption of this rule.
	 *
	 * @var    string
	 */
	protected $title = 'COM_JEDCHECKER_RULE_JAMSS';

	/**
	 * The description of this rule.
	 *
	 * @var    string
	 */
	protected $description = 'COM_JEDCHECKER_RULE_JAMSS_DESC';

	/**
	 * The ordering value to sort rules in the menu.
	 *
	 * @var    integer
	 */
	public static $ordering = 1000;

	protected $ext;

	protected $patterns;

	protected $jamssFileNames;

	/**
	 * Initiates the file search and check
	 *
	 * @return    void
	 */
	public function check()
	{
		$files = JFolder::files($this->basedir, '', true, true);

		$this->init_jamss();

		foreach ($files as $file)
		{
			$this->find($file);
		}
	}

	/**
	 * reads a file and searches for any function defined in the params
	 *
	 * @param   string  $file  The file name
	 *
	 * @return    boolean            True if the statement was found, otherwise False.
	 */
	protected function find($file)
	{
		$content = (array) file($file);
		$result = false;

		$this->scan_file($file);

		return $result;
	}

	/**
	 * This will initialize the local variables for use by jamss.
	 * In order to make this easy to update, no syntax changes are applied. Just store the variables in the object
	 * for faster use.
	 *
	 * @param   bool  $deepscan  Merge the $jamssDeepSearchStrings
	 *
	 * @return void
	 */
	private function init_jamss($deepscan = true)
	{
		/*
		 * START OF JAMSS CODE (approx line 76)
		*/

		/* * * * * Patterns Start * * * * */
		$jamssStrings = 'r0nin|m0rtix|upl0ad|r57shell|c99shell|shellbot|phpshell|void\.ru|';
		$jamssStrings .= 'phpremoteview|directmail|bash_history|multiviews|cwings|vandal|bitchx|';
		$jamssStrings .= 'eggdrop|guardservices|psybnc|dalnet|undernet|vulnscan|spymeta|raslan58|';
		$jamssStrings .= 'Webshell|str_rot13|FilesMan|FilesTools|Web Shell|ifrm|bckdrprm|';
		$jamssStrings .= 'hackmeplz|wrgggthhd|WSOsetcookie|Hmei7|Inbox Mass Mailer|HackTeam|Hackeado|';
		$jamssStrings .= 'Janissaries|Miyachung|ccteam|Adminer|OOO000000|$GLOBALS|findsysfolder';

		// These patterns will be used if GET parameter ?deepscan=1 is set while calling jamss.php file
		$jamssDeepSearchStrings = 'eval|base64_decode|base64_encode|gzdecode|gzdeflate|';
		$jamssDeepSearchStrings .= 'gzuncompress|gzcompress|readgzfile|zlib_decode|zlib_encode|';
		$jamssDeepSearchStrings .= 'gzfile|gzget|gzpassthru|iframe|strrev|lzw_decompress|strtr|';
		$jamssDeepSearchStrings .= 'exec|passthru|shell_exec|system|proc_|popen';

		// The patterns to search for
		$jamssPatterns = array(
				array('preg_replace\s*\(\s*[\"\']\s*(\W)(?-s).*\1[imsxADSUXJu\s]*e[imsxADSUXJu\s]*[\"\'].*\)',
						// [0] = RegEx search pattern
						'PHP: preg_replace Eval',
						// [1] = Name / Title
						'1',
						// [2] = number
						'Detected preg_replace function that evaluates (executes) mathed code. ' .
						'This means if PHP code is passed it will be executed.',
						// [3] = description
						'php',
						// [4] = scope:
						//       'full' - entire file,
						//       'clean' - with stripped PHP comments,
						//       'php' - with stripped HTML and PHP comments,
						//       'code' - with stripped HTML, PHP comments, and strings
						'Part example code from http://sucuri.net/malware/backdoor-phppreg_replaceeval'),
						// [5] = More Information link
						array('c999*sh_surl',
						'Backdoor: PHP:C99:045',
						'2',
						'Detected the "C99? backdoor that allows attackers to manage (and reinfect) your site remotely. ' .
						'It is often used as part of a compromise to maintain access to the hacked sites.',
						'php',
						'http://sucuri.net/malware/backdoor-phpc99045'),
						array('preg_match\s*\(\s*\"\s*/\s*bot\s*/\s*\"',
						'Backdoor: PHP:R57:01',
						'3',
						'Detected the "R57? backdoor that allows attackers to access, modify and reinfect your site. ' .
						'It is often hidden in the filesystem and hard to find without access to the server or logs.',
						'php',
						'http://sucuri.net/malware/backdoor-phpr5701'),
						array('eval[\s/\*\#]*\(stripslashes[\s/\*\#]*\([\s/\*\#]*\$_(REQUEST|POST|GET)\s*\[\s*\\\s*[\'\"]\s*asc\s*\\\s*[\'\"]',
						'Backdoor: PHP:GENERIC:07',
						'5',
						'Detected a generic backdoor that allows attackers to ' .
						'upload files, delete files, access, modify and/or reinfect your site. ' .
						'It is often hidden in the filesystem and hard to find without access to the server or logs. ' .
						'It also includes uploadify scripts and similars that offer upload options without security. ',
						'php',
						'http://sucuri.net/malware/backdoor-phpgeneric07'),
						/*array('https?\S{1,63}\.ru',
						 'russian URL',
						 '6',
						 'Detected a .RU domain link, as there are many attacks leading the innocent visitors to .RU pages.
						 Maybe i\'s valid link, but we leave it to you to check this out.',
						),*/
						array('preg_replace\s*\(\s*[\"\'\”]\s*/\s*\.\s*\*\s*/\s*e\s*[\"\'\”]\s*,\s*[\"\'\”]\s*\\x65\\x76\\x61\\x6c',
						'Backdoor: PHP:Filesman:02',
						'7',
						'Detected the “Filesman” backdoor that allows attackers to access, modify and reinfect your site. ' .
						'It is often hidden in the filesystem and hard to find without access to the server or logs.',
						'php',
						'http://sucuri.net/malware/backdoor-phpfilesman02'),
						array('(include|require)(_once)*\s*[\"\'][\w\W\s/\*]*php://input[\w\W\s/\*]*[\"\']',
						'PHP:\input include',
						'8',
						'Detected the method of reading input through PHP protocol handler in include/require statements.',
						'php'),
						array('data:;base64',
						'data:;base64 include',
						'9',
						'Detected the method of executing base64 data in include.',
						'php'),
						array('RewriteCond\s*%\{HTTP_REFERER\}',
						'.HTACCESS RewriteCond-Referer',
						'10',
						'Your .htaccess file has a conditional redirection based on "HTTP Referer". ' .
						'This means it redirects according to site/url from where your visitors came to your site. ' .
						'Such technique has been used for unwanted redirections after coming from Google or other search engines, ' .
						'so check this directive carefully.',
						'full'),
						array('brute\s*force',
						'"Brute Force" words',
						'11',
						'Detected the "Brute Force" words mentioned in code. <u>Sometimes it\'s a "false positive"</u> because ' .
						'several developers like to mention it in they code, but it\'s worth double-checking if this file ' .
						'is untouched (eg. compare it with one in original extension package).',
						'full'),
						array('GIF89a.*[\r\n]*.*<\?php',
						'PHP file desguised as GIF image',
						'15',
						'Detected a PHP file that was most probably uploaded as an image via webform that loosely only checks ' .
						'file headers.',
						'full'),
						array('\$ip[\w\W\s/\*]*=[\w\W\s/\*]*getenv\(["\']REMOTE_ADDR["\']\);[\w\W\s/\*]*[\r\n]\$message',
						'Probably malicious PHP script that "calls home"',
						'16',
						'Detected script variations often used to inform the attackers about found vulnerable website.',
						'php'),
						array('(?:\b(?:eval|gzuncompress|gzinflate|base64_decode|str_rot13|strrev|strtr|rawurldecode|' .
						'assert|unpack|urldecode)[\s/\*\w\W\(]*){2,}',
						'PHP: multiple encoded, most probably obfuscated code found',
						'17',
						'This pattern could be used in highly encoded, malicious code hidden under a loop of code obfuscation function ' .
						'calls. In most cases the decoded hacker code goes through an eval call to execute it. ' .
						'This pattern is also often used for legitimate purposes, e.g. storing configuration information or ' .
						'serialised object data. ' .
						'Please inspect the file manually and compare it with the one in the original extension or ' .
						'Joomla package to verify that this is not a false positive.',
						'code',
						'Thanks to Dario Pintarić (dario.pintaric[et}orion-web.hr for this report!'),
						array('<\s*iframe',
						'IFRAME element',
						'18',
						'Found IFRAME element in code. It\'s mostly benevolent, but often used for bad stuff, ' .
						'so please check if it\'s a valid code.',
						'clean'),
						array('strrev[\s/\*\#]*\([\s/\*\#]*[\'"]\s*tressa\s*[\'"]\s*\)',
						'Reversed string "assert"',
						'19',
						'Assert function name is being hidden behind strrev().',
						'php'),
						array('is_writable[\s/\*\#]*\([\s/\*\#]*getcwd',
						'Is the current DIR Writable?',
						'20',
						'This could be harmless, but used in some malware',
						'code'),
						array('(?:\\\\x[0-9A-Fa-f]{1,2}|\\\\[0-7]{1,3}){2,}',
						'At least two characters in hexadecimal or octal notation',
						'21',
						'Found at least two characters in hexadecimal or octal notation. It doesn\'t mean it is malicious, ' .
						'but it could be code hidding behind such notation.',
						'php'),
						array('\$_F\s*=\s*__FILE__\s*;\s*\$_X\s*=',
						'SourceCop encoded code',
						'22',
						'Found the SourceCop encoded code. It is often used for malicious code ' .
						'hiding, so go and check the code with some online SourceCop decoders',
						'code'),
						array('\b(?:exec|passthru|shell_exec|system|proc_\w+|popen)\b[\w\W\s/\*]*\([\s/\*\#\'\"\w\W\-\_]*(?:\$_GET|\$_POST)',
						'shell command execution from POST/GET variables',
						'23',
						'Found direct shell command execution getting variables from POST/GET, ' .
						'which is highly dangerous security flaw or a part of malicious webrootkit',
						'code'),
						array('`',
						'PHP execution operator: backticks (``)',
						'24',
						'PHP execution operator found. Note that these are not single-quotes! ' .
						'PHP will attempt to execute the contents of the backticks as a shell ' .
						'command, which might indicate a part of a webrootkit',
						'code'),
		);

		$jamssFileNames = array(
				'Probably an OpenFlashChart library demo file that has known input validation error (CVE-2009-4140)'
				=> 'ofc_upload_image.php',
				'Probably an R57 shell'
				=> 'r57.php',
				'PhpInfo() file? It is advisable to remove such file, as it could reveal too
				much info to potential attackers'
				=> 'phpinfo.php',
		);

		/* * * * * Patterns End * * * * */

		// Check if DeepScan should be done
		if (isset($_GET['deepscan']))
		{
			$patterns = array_merge($jamssPatterns, explode('|', $jamssStrings), explode('|', $jamssDeepSearchStrings));
		}
		else
		{
			$patterns = array_merge($jamssPatterns, explode('|', $jamssStrings));
		}

		/*
		 * END OF JAMSS CODE (approx line 203)
		*/

		$this->patterns = $patterns;
		$this->jamssFileNames = $jamssFileNames;
		$valid_extensions = explode('|', $this->params->get('fileExt'));
		$this->ext = $valid_extensions;
	}

	/**
	 * Scan given file for all malware patterns
	 *
	 * @param   string  $path  path of the scanned file
	 *
	 * @return  bool
	 */
	private function scan_file($path)
	{
		// Init the variables scan_file expects:
		$ext = $this->ext;
		$patterns = $this->patterns;
		$count = 0;
		$total_results = 0;
		$jamssFileNames = $this->jamssFileNames;

		// Removed: global $ext, $patterns, $count, $total_results, $jamssFileNames;

		/**
		 * JAMSS Code, line 251 (all output functions were changed to conform to jedchecker output)
		 */

		if (in_array(pathinfo($path, PATHINFO_EXTENSION), $ext)
			&& filesize($path)/* skip empty ones */
			&& !stripos($path, 'jamss.php')/* skip this file */)
			{
			if ($malic_file_descr = array_search(pathinfo($path, PATHINFO_BASENAME), $jamssFileNames))
			{
				$this->jamssWarning($path, JText::_('COM_JEDCHECKER_ERROR_JAMSS_SUSPICIOUS_FILENAME'), $malic_file_descr, '', 0);
			}

			if (!($content = file_get_contents($path)))
			{
				$this->report->addError($path, JText::_('COM_JEDCHECKER_ERROR_JAMSS_CANNOT_OPEN') . $malic_file_descr, 0);

				return true;
			}
			else
			{
				$scopes = array(
					'full' => $content,
					'clean' => JEDCheckerHelper::cleanPhpCode($content, JEDCheckerHelper::CLEAN_COMMENTS),
					'php' =>   JEDCheckerHelper::cleanPhpCode($content, JEDCheckerHelper::CLEAN_COMMENTS | JEDCheckerHelper::CLEAN_HTML),
					'code' =>  JEDCheckerHelper::cleanPhpCode($content, JEDCheckerHelper::CLEAN_COMMENTS | JEDCheckerHelper::CLEAN_HTML | JEDCheckerHelper::CLEAN_STRINGS)
				);

				// Do a search for fingerprints
				foreach ($patterns as $pattern)
				{
					$scope = (is_array($pattern) && isset($pattern[4])) ? $pattern[4] : 'clean';
					$scoped_content = $scopes[$scope];

					if (is_array($pattern))
					{
						// It's a pattern
						// RegEx modifiers: i=case-insensitive; s=dot matches also newlines; S=optimization
						preg_match_all('#' . $pattern[0] . '#isS', $scoped_content, $found, PREG_OFFSET_CAPTURE);
					}
					else
					{
						// It's a string
						preg_match_all('#' . $pattern . '#isS', $scoped_content, $found, PREG_OFFSET_CAPTURE);
					}

					// Remove outer array from results
					$all_results = $found[0];

					// Count the number of results
					$results_count = count($all_results);

					// Total results of all fingerprints
					$total_results += $results_count;

					// Added to avoid notices.
					$first_line = 0;

					$first_code = "";

					if (!empty($all_results))
					{
						$count++;

						foreach ($all_results as $match)
						{
							// Output the line of malware code, but sanitize it before
							// The offset is in $match[1]
							$offset = $match[1];
							// Note: negative 3rd argument is used for right-to-left search
							$start = strrpos($scoped_content, "\n", $offset - strlen($scoped_content));

							if ($start === false)
							{
								$start = 0;
							}

							$end = strpos($scoped_content, "\n", $offset);

							if ($end === false)
							{
								$end = strlen($scoped_content);
							}

							$first_code = substr($scoped_content, $start, min($end - $start, 200));
							$first_line = $this->calculate_line_number($offset, $scoped_content);
							break;
						}

						if (is_array($pattern))
						{
							// Then it has some additional comments
							$this->jamssWarning(
									$path,
									JText::_('COM_JEDCHECKER_ERROR_JAMSS_PATTERN') . "#$pattern[2] - $pattern[1]",
									$pattern[3],
									$first_code,
									$first_line
								);
						}
						else
						{
							// It's a string, no comments available
							$this->jamssWarning(
									$path,
									JText::_('COM_JEDCHECKER_ERROR_JAMSS_STRING') . $pattern,
									'',
									$first_code,
									$first_line
								);
						}
					}
				}
			}
		}

		return false;
	}

	/**
	 * Calculates the line number where pattern match was found
	 *
	 * @param   int       $length          The maximum length after the specified offset to search for the line breaks
	 * @param   string    $fileContent     The file content in string format
	 * @param   int       $offset          The offset where to start counting (default 0)
	 *
	 * @return  int  Returns        line number where the subject code was found
	 */
	private function calculate_line_number($length, $fileContent, $offset = 0)
	{
		// We are counting the number of line breaks between 
		// the offset, and length in the file content.
		// Then we add one since we get a zero
		// based return value from substr_count function.
		return substr_count($fileContent, "\n", $offset, $length) + 1;
	}

	/**
	 * Raise a warning and format it properly.
	 * jamss warnings are very helpful but very verbose; hence we chose to use of tooltips
	 *
	 * @param   string  $path   The file name
	 * @param   string  $title  The comment's title
	 * @param   mixed  $info   The additional info on the error
	 * @param   string  $code   The affected portion of the code
	 * @param   int  $line   The line number of the first match
	 *
	 * @return  void          Returns nothing
	 */
	private function jamssWarning($path, $title, $info, $code, $line)
	{
		$info = !empty($info) ? sprintf($this->params->get('info'), htmlentities($info, ENT_QUOTES)) : '';
		$this->report->addWarning($path, $info . $title, $line, $code);
	}
}
