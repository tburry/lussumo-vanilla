<?php
// REPORT ALL ERRORS
error_reporting(E_ALL);
// DO NOT ALLOW PHP_SESS_ID TO BE PASSED IN THE QUERYSTRING
ini_set('session.use_only_cookies', 1);
// Track errors so explicit error messages can be reported should errors be encountered
ini_set('track_errors', 1);
// Define constant for magic_quotes
define('MAGIC_QUOTES_ON', get_magic_quotes_gpc());

// INCLUDE NECESSARY CLASSES & FUNCTIONS
include('../library/Framework/Framework.Functions.php');
include('../library/Framework/Framework.Class.Select.php');
include('../library/Framework/Framework.Class.SqlBuilder.php');
include('../library/Framework/Framework.Class.MessageCollector.php');
include('../library/Framework/Framework.Class.ErrorManager.php');
include('../library/Framework/Framework.Class.ConfigurationManager.php');

// Include database structure
include('../appg/database.php');

// Set up some configuration defaults to override
$Configuration['DATABASE_HOST'] = ''; 
$Configuration['DATABASE_NAME'] = ''; 
$Configuration['DATABASE_USER'] = ''; 
$Configuration['DATABASE_PASSWORD'] = ''; 
$Configuration['APPLICATION_PATH'] = '';
$Configuration['DATABASE_PATH'] = ''; 
$Configuration['LIBRARY_PATH'] = ''; 
$Configuration['EXTENSIONS_PATH'] = ''; 
$Configuration['LANGUAGES_PATH'] = '';
$Configuration['THEME_PATH'] = '';
$Configuration['BASE_URL'] = '';
$Configuration['DEFAULT_STYLE'] = ''; 
$Configuration['WEB_ROOT'] = '';
$Configuration['COOKIE_DOMAIN'] = ''; 
$Configuration['COOKIE_PATH'] = ''; 
$Configuration['SUPPORT_EMAIL'] = ''; 
$Configuration['SUPPORT_NAME'] = ''; 
$Configuration['FORWARD_VALIDATED_USER_URL'] = '';
$Configuration['CHARSET'] = 'utf-8';
$Configuration['DATABASE_TABLE_PREFIX'] = 'LUM_';

class FauxContext {
   var $WarningCollector;
   var $ErrorManager;
   var $SqlCollector;
	var $Configuration;
	var $Dictionary;
	var $DatabaseTables;
	var $DatabaseColumns;
	var $Session;
	function GetDefinition($Code) {
		if (array_key_exists($Code, $this->Dictionary)) {
			return $this->Dictionary[$Code];
		} else {
			return $Code;
		}
	}
}

class FauxSession {
	var $User = false;
}

// Create warning & error handlers
$Context = new FauxContext();
$Context->Session = new FauxSession();
$Context->WarningCollector = new MessageCollector();
$Context->ErrorManager = new ErrorManager();
$Context->SqlCollector = new MessageCollector();
$Context->Configuration = $Configuration;
$Context->DatabaseTables = $DatabaseTables;
$Context->DatabaseColumns = $DatabaseColumns;
$Context->Dictionary = array();

// Dictionary Definitions
$Context->Dictionary['ErrReadFileSettings'] = 'An error occurred while attempting to read settings from the configuration file: ';
$Context->Dictionary['ErrOpenFile'] = 'The file could not be opened. Please make sure that PHP has write access to the //1 file.';
$Context->Dictionary['ErrWriteFile'] = 'The file could not be written.';

// Retrieve all postback parameters
$CurrentStep = ForceIncomingInt("Step", 0);
$PostBackAction = ForceIncomingString('PostBackAction', '');
$DBHost = ForceIncomingString('DBHost', '');
$DBName = ForceIncomingString('DBName', '');
$DBUser = ForceIncomingString('DBUser', '');
$DBPass = ForceIncomingString('DBPass', '');
$Username = ForceIncomingString('Username', '');
$Password = ForceIncomingString('Password', '');
$ConfirmPassword = ForceIncomingString('ConfirmPassword', '');
$SupportEmail = ForceIncomingString('SupportEmail', '');
$SupportName = ForceIncomingString('SupportName', '');
$ApplicationTitle = ForceIncomingString('ApplicationTitle', 'Vanilla');
$CookieDomain = ForceIncomingString('CookieDomain', '');
$CookiePath = ForceIncomingString('CookiePath', '');
// Make the banner title the same as the application title
$WorkingDirectory = str_replace('\\', '/', getcwd()).'/';
$RootDirectory = str_replace('setup/', '', $WorkingDirectory);
$WebRoot = dirname(ForceString(@$_SERVER['PHP_SELF'], ''));
$WebRoot = substr($WebRoot, 0, strlen($WebRoot) - 5); // strips the "setup" off the end of the path.
$BaseUrl = 'http://'.ForceString(@$_SERVER['HTTP_HOST'], '').$WebRoot;
$ThemeDirectory = $WebRoot . 'themes/';
$AllowNext = 0;

function CreateFile($File, $Contents, &$Context) {
	if (!file_exists($File)) {
		$Handle = fopen($File, 'wb');
		if (!$Handle) {
			$Context->WarningCollector->Add("Failed to create the '".$File."' configuration file.");
		} else {
			if (fwrite($Handle, $Contents) === FALSE) {
				$Context->WarningCollector->Add("Failed to write to the '".$File."' file. Make sure that PHP has write access to the file.");
			}
			fclose($Handle);
		}
	}
}

// Step 1. Check for correct PHP, MySQL, and permissions
if ($PostBackAction == "Permissions") {
   
   // Make sure we are running at least PHP 4.1.0
   if (intval(str_replace('.', '', phpversion())) < 410) $Context->WarningCollector->Add("It appears as though you are running PHP version ".phpversion().". Vanilla requires at least version 4.1.0 of PHP. You will need to upgrade your version of PHP before you can continue.");
   // Make sure MySQL is available
	if (!function_exists('mysql_connect')) $Context->WarningCollector->Add("It appears as though you do not have MySQL enabled for PHP. You will need a working copy of MySQL and PHP's MySQL extensions enabled in order to run Vanilla.");   
   // Make sure the conf folder is writeable
   if (!is_writable('../conf/')) $Context->WarningCollector->Add("Vanilla needs to have write permission enabled on the conf folder.");
	
	if ($Context->WarningCollector->Count() == 0) {
		$Contents = '<?php
// Database Configuration Settings
?>';
		CreateFile($RootDirectory.'conf/database.php', $Contents, $Context);
		$Contents = '<?php
// Enabled Extensions
?>';
		CreateFile($RootDirectory.'conf/extensions.php', $Contents, $Context);
		$Contents = '<?php
// Custom Language Definitions
?>';
		CreateFile($RootDirectory.'conf/language.php', $Contents, $Context);
		$Contents = '<?php
// Application Settings
?>';
		CreateFile($RootDirectory.'conf/settings.php', $Contents, $Context);
	}

   // Save general settings
	if ($Context->WarningCollector->Count() == 0) {
      $SettingsFile = $RootDirectory . 'conf/settings.php';
      $SettingsManager = new ConfigurationManager($Context);
      $SettingsManager->DefineSetting('APPLICATION_PATH', $RootDirectory, 1);
		$SettingsManager->DefineSetting('DATABASE_PATH', $RootDirectory . 'conf/database.php', 1);
		$SettingsManager->DefineSetting('LIBRARY_PATH', $RootDirectory . 'library/', 1);
		$SettingsManager->DefineSetting('EXTENSIONS_PATH', $RootDirectory . 'extensions/', 1);
		$SettingsManager->DefineSetting('LANGUAGES_PATH', $RootDirectory . 'languages/', 1);
		$SettingsManager->DefineSetting('THEME_PATH', $RootDirectory . 'themes/Vanilla/', 1);
		$SettingsManager->DefineSetting("DEFAULT_STYLE", $ThemeDirectory.'Vanilla/styles/default/', 1);
		$SettingsManager->DefineSetting("WEB_ROOT", $WebRoot, 1);
      $SettingsManager->DefineSetting("BASE_URL", $BaseUrl, 1);
		$SettingsManager->DefineSetting("FORWARD_VALIDATED_USER_URL", $BaseUrl, 1);
      if (!$SettingsManager->SaveSettingsToFile($SettingsFile)) {
         // $Context->WarningCollector->Clear();
         $Context->WarningCollector->Add("For some reason we couldn't save your general settings to the '".$SettingsFile."' file.");
      }
		$Context->WarningCollector->Write();
	}
      
   if ($Context->WarningCollector->Count() == 0) {
		// Redirect to the next step (this is done so that refreshes don't cause steps to be redone)
      header('location: '.$WebRoot.'setup/installer.php?Step=2');
		die();
	}
} elseif ($PostBackAction == "Database") {
	$CurrentStep = 2;
   // Test the database params provided by the user
   $Connection = @mysql_connect($DBHost, $DBUser, $DBPass);
   if (!$Connection) {
		$Response = '';
		if ($php_errormsg != '') $Response = ' The database responded with the following message: '.$php_errormsg;
      $Context->WarningCollector->Add("We couldn't connect to the server you provided (".$DBHost.").".$Response);
   } elseif (!mysql_select_db($DBName, $Connection)) {
      $Context->WarningCollector->Add("We connected to the server, but we couldn't access the \"".$DBName."\" database. Are you sure it exists and that the specified user has access to it?");
   }
   
   // If the database connection worked, attempt to set up the database
   if ($Context->WarningCollector->Count() == 0 && $Connection) {
      // Make sure there are no conflicting tables in the database
      $TableData = @mysql_query('show tables', $Connection);
      if (!$TableData) {
         $Context->WarningCollector->Add("We had some problems identifying the tables already in your database: ". mysql_error($Connection));
      } else {
         $TableConflicts = array();
			$TableToCompare = '';
         while ($Row = mysql_fetch_array($TableData)) {
				$TableToCompare = $Row[0];
				$TableToCompare = str_replace('LUM_', '', $TableToCompare);
            if (array_key_exists($TableToCompare, $DatabaseTables)) {
               $TableConflicts[] = $Row[0];
            }
         }
         if (count($TableConflicts) == count($DatabaseTables)) {
            $Context->WarningCollector->Add("It appears as though you've already got Vanilla installed. If you are attempting to upgrade your existing installation of Vanilla, you should be using the <a href=\"upgrader.php\">upgrade script</a>.");
            $AllowNext = 1;
         } elseif (count($TableConflicts) > 0) {
            $Context->WarningCollector->Add("There appear to be some tables already in your database that conflict with the tables Vanilla would need to insert. Those tables are: <code>".implode(', ', $TableConflicts)."</code>If you are attempting to upgrade your existing installation of Vanilla, you should be using the <a href=\"upgrader.php\">upgrade script</a>.");
         } else {
            // Go ahead and install the database tables
            // Open the database file & retrieve sql
            $SqlLines = @file($WorkingDirectory."mysql.sql");
            if (!$SqlLines) {
               $Context->WarningCollector->Add("We couldn't open the \"".$WorkingDirectory."mysql.sql\" file.");
            } else {
               $CurrentQuery = "";
               $CurrentLine = "";
               for ($i = 0; $i < count($SqlLines); $i++) {
                  $CurrentLine = trim($SqlLines[$i]);
                  if ($CurrentLine == "") {
                     if ($CurrentQuery != "") {
                        if (!@mysql_query($CurrentQuery, $Connection)) {
                           $Context->WarningCollector->Add("An error occurred while we were attempting to create the database tables. MySQL reported the following error: <code>".mysql_error($Connection).'</code><code>QUERY: '.$CurrentQuery.'</code>');
                           $i = count($SqlLines)+1;
                        }
                        $CurrentQuery = "";
                     }
                  } else {
                     $CurrentQuery .= $CurrentLine;
                  }
               }
            }
         }      
      }
      // Close the database connection
      @mysql_close($Connection);
   }
   
   // If the database was created successfully, save all parameters to the conf/database.php file
   if ($Context->WarningCollector->Count() == 0) {
      // Save database settings
      $DBFile = $RootDirectory . 'conf/database.php';
      $DBManager = new ConfigurationManager($Context);
      $DBManager->DefineSetting("DATABASE_HOST", $DBHost, 1);
      $DBManager->DefineSetting("DATABASE_NAME", $DBName, 1);
      $DBManager->DefineSetting("DATABASE_USER", $DBUser, 1);
      $DBManager->DefineSetting("DATABASE_PASSWORD", $DBPass, 1);
      if (!$DBManager->SaveSettingsToFile($DBFile)) {
         $Context->WarningCollector->Clear();
         $Context->WarningCollector->Add("For some reason we couldn't save your database settings to the '.$DBFile.' file.");
      }
   }
   if ($Context->WarningCollector->Count() == 0) {
		// Redirect to the next step (this is done so that refreshes don't cause steps to be redone)
      header('location: '.$WebRoot.'setup/installer.php?Step=3');
		die();
   }
} elseif ($PostBackAction == "User") {
	$CurrentStep = 3;	
   // Validate user inputs
   if (strip_tags($Username) != $Username) $Context->WarningCollector->Add("You really shouldn't have any html into your username.");
   if (strlen($Username) > 20) $Context->WarningCollector->Add("Your username is too long");
   if ($Password != $ConfirmPassword) $Context->WarningCollector->Add("The passwords you entered didn't match.");
   if (!eregi("(.+)@(.+)\.(.+)", $SupportEmail)) $Context->WarningCollector->Add("The email address you entered doesn't appear to be valid.");
   if (strip_tags($ApplicationTitle) != $ApplicationTitle) $Context->WarningCollector->Add("You can't have any html in your forum name.");
   if ($Username == "") $Context->WarningCollector->Add("You must provide a username.");
   if ($Password == "") $Context->WarningCollector->Add("You must provide a password.");
   if ($SupportName == "") $Context->WarningCollector->Add("You must provide a support contact name.");
   if ($ApplicationTitle == "") $Context->WarningCollector->Add("You must provide an application title.");
   
	// Include the db settings defined in the previous step
   include($RootDirectory.'conf/database.php');
	
   // Open the database connection
   $Connection = false;
   if ($Context->WarningCollector->Count() == 0) {
		$DBHost = $Configuration['DATABASE_HOST'];
		$DBName = $Configuration['DATABASE_NAME'];
		$DBUser = $Configuration['DATABASE_USER'];
		$DBPass = $Configuration['DATABASE_PASSWORD'];
      $Connection = @mysql_connect($DBHost, $DBUser, $DBPass);
      if (!$Connection) {
         $Context->WarningCollector->Add("We couldn't connect to the server you provided (".$DBHost."). Are you sure you entered the right server, username and password?");
      } elseif (!mysql_select_db($DBName, $Connection)) {
         $Context->WarningCollector->Add("We connected to the server, but we couldn't access the \"".$DBName."\" database. Are you sure it exists and that the specified user has access to it?");
      }
   }
   
   // Create the administrative user
   if ($Context->WarningCollector->Count() == 0 && $Connection) {
      $Username = FormatStringForDatabaseInput($Username);
      $Password = FormatStringForDatabaseInput($Password);
      
      $s = new SqlBuilder($Context);
      $s->SetMainTable('User', 'u');
      $s->AddFieldNameValue('FirstName', 'Administrative');
      $s->AddFieldNameValue('LastName', 'User');
      $s->AddFieldNameValue('Email', FormatStringForDatabaseInput($SupportEmail));
      $s->AddFieldNameValue('Name', $Username);
      $s->AddFieldNameValue('Password', $Password, 1, 'md5');
		$s->AddFieldNameValue('DateFirstVisit', MysqlDateTime());
		$s->AddFieldNameValue('DateLastActive', MysqlDateTime());
		$s->AddFieldNameValue('CountVisit', 0);
		$s->AddFieldNameValue('CountDiscussions', 0);
		$s->AddFieldNameValue('CountComments', 0);
		$s->AddFieldNameValue('RoleID', 4);
		$s->AddFieldNameValue('StyleID', 1);
		$s->AddFieldNameValue('UtilizeEmail', 0);
		$s->AddFieldNameValue('RemoteIp', GetRemoteIp(1));
		if (!@mysql_query($s->GetInsert(), $Connection)) {
         $Context->WarningCollector->Add("Something bad happened when we were trying to create your administrative user account. Mysql said: ".mysql_error($Connection));
      } else {
         // Now insert the role history assignment
			$NewUserID = mysql_insert_id($Connection);
			$s->Clear();
			$s->SetMainTable('UserRoleHistory', 'h');
			$s->AddFieldNameValue('UserID', $NewUserID);
			$s->AddFieldNameValue('RoleID', 4);
			$s->AddFieldNameValue('Date', MysqlDateTime());
			$s->AddFieldNameValue('AdminUserID', $NewUserID);
			$s->AddFieldNameValue('Notes', 'Initial administrative account created');
			$s->AddFieldNameValue('RemoteIp', GetRemoteIp(1));
         // Fail silently on this one
         @mysql_query($s->GetInsert(), $Connection);
      }
		// Create the default Vanilla style entry in the db
		$s->Clear();
		$s->SetMainTable('Style', 's');
		$s->AddFieldNameValue('Name', 'Vanilla');
		$s->AddFieldNameValue('Url', $ThemeDirectory.'Vanilla/styles/default/');
		@mysql_query($s->GetInsert(), $Connection);
   }
   
   // Close the database connection
   @mysql_close($Connection);
   
   // Save the application constants
   if ($Context->WarningCollector->Count() == 0) {
      $SettingsFile = $RootDirectory . 'conf/settings.php';
      $SettingsManager = new ConfigurationManager($Context);
      $SettingsManager->DefineSetting("SUPPORT_EMAIL", $SupportEmail, 1);
      $SettingsManager->DefineSetting("SUPPORT_NAME", $SupportName, 1);
      $SettingsManager->DefineSetting("APPLICATION_TITLE", $ApplicationTitle, 1);
      $SettingsManager->DefineSetting("BANNER_TITLE", $ApplicationTitle, 1);
      $SettingsManager->DefineSetting("COOKIE_DOMAIN", $CookieDomain, 1);
      if (!$SettingsManager->SaveSettingsToFile($SettingsFile)) {
         $Context->WarningCollector->Clear();
         $Context->WarningCollector->Add("For some reason we couldn't save your general settings to the '.$SettingsFile.' file.");
      }
   }
   
   if ($Context->WarningCollector->Count() == 0) {
		// Redirect to the next step (this is done so that refreshes don't cause steps to be redone)
      header('location: '.$WebRoot.'setup/installer.php?Step=4');
		die();
	}
} 
   
// Write the page
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-ca">
   <head>
      <title>Vanilla 1 Installer</title>
      <style type="text/css">
         body {
            background: #fff;
            margin: 0px;
            padding: 0px;
				text-align: center;
         }
			body, div, p, blockquote, h1, h2, input {
            font-family: Trebuchet MS, Arial, Verdana;
			}
         body, div, p, blockquote {
            font-size: 13px;
            line-height: 170%;
         }
         a, a:link, a:visited {
            text-decoration: underline;
            color: #f60;
         }
         a:hover {
            text-decoration: underline;
            color: #c30;
         }
			h1 {
				background: #F1F3FF;
				border-bottom: 1px solid #ACBEDF;
				margin: 0px;
				padding: 0px;
			}
			h1 span {
				background: url(leaf.gif) no-repeat center left;
				color: #ACBEDF;
				display: block;
				width: 500px;
				margin-left: auto;
				margin-right: auto;
				font-size: 36px;
				padding: 20px 0px 20px 0px;
				text-align: left;
			}
			h1 span strong {
				padding-left: 80px;
				color: #758ebc;
			}
			.Container {
				background: url(fade.jpg) top left repeat-x;
			}
			.Content {
				margin-left: auto;
				margin-right: auto;
				text-align: left;
				width: 500px;
				padding: 20px 0px 0px 0px;
			}
			h2 {
				margin: 0px;
				padding: 0px;
			}
			code {
				background: #FEFECC;
				margin: 10px 0px 10px 0px;
				padding: 10px;
				border-left: 4px solid #FCEEAA;
				color: #960;
				font-family: courier;
				font-size: 12px;
				display: block;
			}
			fieldset {
				border: 0px;
				margin: 0px;
				padding: 0px;
			}
			fieldset ul {
				list-style: none;
				margin: 0px;
				padding: 8px 8px 2px 8px;
				background: #efefef;
			}
			fieldset label {
				float: left;
				margin: 0px 0px 6px 0px;
				width:30%;
			}
			fieldset ul li input {
				margin-bottom: 6px;
				width: 50%;
			}
			.Button {
				padding: 10px 0px 20px 0px;
			}
			.Button input {
				font-size: 14px;
				font-weight: bold;
				border: 0px;
				margin: 0px;
				padding: 0px;
				background: none;
				cursor: pointer;
				color: #64AE18;
			}
			.Button a {
				font-size: 14px;
				font-weight: bold;
				color: #64AE18;			
			}
         .Warnings {
				background: url(../themes/vanilla/styles/default/alert.gif) left top no-repeat #FFFECC;
            padding: 8px;
				margin: 10px 0px 0px 0px;
				border-top: 1px solid #FAEBB1;
				border-bottom: 1px solid #FAEBB1;
				color: #D57D00;
         }
         .Warnings,
         .Warnings div {
            color: #D57D00;
         }
         .Warnings strong {
				color: #c00;
            display: block;
            padding: 0px 0px 4px 22px;
         }
			.Warnings div a,
			.Warnings div a:link,
			.Warnings div a:visited {
				color: #B16800;
			}
			.Warnings div a:hover {
				color: #c00;
			}
			.Warnings div code {
				background: none;
				margin: 10px 0px 10px 0px;
				padding: 0px;
				border: 0px;
				color: #960;
				font-family: courier;
				font-size: 12px;
				display: block;
			}
      </style>
   </head>
   <body>
      <h1>
         <span><strong>Vanilla 1</strong> Installer</span>
      </h1>
		<div class="Container">
			<div class="Content">
			<?php
			if ($CurrentStep < 2 || $CurrentStep > 4) {
				echo  '<h2>Vanilla Installation Wizard (Step 1 of 3)</h2>';
				if ($Context->WarningCollector->Count() > 0) {
					echo "<div class=\"Warnings\">
						<strong>We came across some problems while checking your permissions...</strong>
						".$Context->WarningCollector->GetMessages()."
					</div>";
				}
				echo "<p>Vanilla stores configuration settings in PHP files. In order for you to be able to edit these options in Vanilla, you will need to make sure that PHP has read and write access to these configuration files.</p>
				<p>The recommended way to do this is to make the apache (or IIS) user and group the owner of the conf directory. If you are running a Linux/Unix/Mac server and you have command line access, you can achieve these permissions by navigating to the Vanilla folder and executing the following:</p>
				<p><strong>Note:</strong> This assumes that your apache user and group are www-data and www-data respectively. Depending on your operating system, this could be different. Other standard apache users are <em>apache</em> and <em>nobody</em>.</p>
				<code>chown www-data:www-data ./conf</code>
				
				<p>If you don't know what your apache user is, you can also grant full access to the configuration folder for all users. This is not recommended because it is not as secure as the previous method, but it can be accomplished from the root Vanilla folder by executing the following:</p>
				<code>chmod 666 ./conf</code>
				
				<p>Next, you need to make sure that Vanilla has read access to the setup directory so it can retrieve the database setup script for installation. You can do this by executing the following from the root folder of Vanilla:</p>
				<code>chmod 666 ./setup/mysql.sql</code>
				
				<form id=\"frmPermissions\" method=\"post\" action=\"installer.php\">
				<input type=\"hidden\" name=\"PostBackAction\" value=\"Permissions\" />
				<div class=\"Button\"><input type=\"submit\" value=\"Click here to check your permissions and proceed to the next step\" /></div>
				</form>";
			} elseif ($CurrentStep == 2) {
					echo "<h2>Vanilla Installation Wizard (Step 2 of 3)</h2>";
					if ($Context->WarningCollector->Count() > 0) {
						echo "<div class=\"Warnings\">
							<strong>We came across some problems while setting up Vanilla...</strong>
							".$Context->WarningCollector->GetMessages()."
						</div>";
					}
					echo "<p>Below you can provide the connection parameters for the mysql server where you want to install Vanilla. If you haven't done it yet, now would be a good time to create the database where you want Vanilla installed.</p>
					<fieldset>
						<form id=\"frmDatabase\" method=\"post\" action=\"installer.php\">
						<input type=\"hidden\" name=\"PostBackAction\" value=\"Database\" />
							<ul>
								<li>
									<label for=\"tDBHost\">MySQL Server</label>
									<input type=\"text\" id=\"tDBHost\" name=\"DBHost\" value=\"".FormatStringForDisplay($DBHost, 1)."\" />
								</li>
								<li>
									<label for=\"tDBName\">MySQL Database Name</label>
									<input type=\"text\" id=\"tDBName\" name=\"DBName\" value=\"".FormatStringForDisplay($DBName, 1)."\" />
								</li>
								<li>
									<label for=\"tDBUser\">MySQL User</label>
									<input type=\"text\" id=\"tDBUser\" name=\"DBUser\" value=\"".FormatStringForDisplay($DBUser, 1)."\" />
								</li>
								<li>
									<label for=\"tDBPass\">MySQL Password</label>
									<input type=\"password\" id=\"tDBPass\" name=\"DBPass\" value=\"".FormatStringForDisplay($DBPass, 1)."\" />
								</li>
							</ul>
							<div class=\"Button\"><input type=\"submit\" value=\"Click here to create Vanilla's database and proceed to the next step\" /></div>
						</form>
					</fieldset>";
				} elseif ($CurrentStep == 3) {
					if ($PostBackAction != "User") {
						$CookieDomain = ForceString(@$_SERVER['HTTP_HOST'], "");
						$CookiePath = $WebRoot;
					}
					echo "<h2>Vanilla Installation Wizard (Step 3 of 3)</h2>";
					if ($Context->WarningCollector->Count() > 0) {
						echo "<div class=\"Warnings\">
							<strong>We came across some problems while setting up Vanilla...</strong>
							".$Context->WarningCollector->GetMessages()."
						</div>";
					}
					echo "<p>Now let's set up your administrative account for Vanilla.</p>
						<fieldset>"
						.'<form name="frmUser" method="post" action="installer.php">
						<input type="hidden" name="PostBackAction" value="User" />
						<ul
							<li>
								<label for="tUsername">Username</label>
								<input id="tUsername" type="text" name="Username" value="'.FormatStringForDisplay($Username, 1).'" />
							</li>
							<li>
								<label for="tPassword">Password</label>
								<input id="tPassword" type="password" name="Password" value="'.FormatStringForDisplay($Password, 1).'" />
							</li>
							<li>
								<label for="tConfirmPassword">Confirm Password</label>
								<input id="tConfirmPassword" type="password" name="ConfirmPassword" value="'.FormatStringForDisplay($ConfirmPassword, 1).'" />
							</li>
						</ul>'
						."<p>Up next we've got to set up the support contact information for your forum. This is what people will see when emails go out from the system for things like password retrieval and role changes.</p>"
						.'<ul>
							<li>
								<label for="tSupportName">Support Contact Name</label>
								<input id="tSupportName" type="text" name="SupportName" value="'.FormatStringForDisplay($SupportName, 1).'" />
							</li>
							<li>
								<label for="tSupportEmail">Support Email Address</label>
								<input id="tSupportEmail" type="text" name="SupportEmail" value="'.FormatStringForDisplay($SupportEmail, 1).'" />
							</li>
						</ul>
						<p>What do you want to call your forum?</p>
						<ul>
							<li>
								<label for="tApplicationTitle">Forum Name</label>
								<input id="tApplicationTitle" type="text" name="ApplicationTitle" value="'.FormatStringForDisplay($ApplicationTitle, 1).'" />
							</li>
						</ul>'
						."<p>The cookie domain is where you want cookies assigned to for Vanilla. Typically the cookie domain will be something like www.yourdomain.com. Cookies can be further defined to a particular path on your website using the \"Cookie Path\" setting. (TIP: If you want your Vanilla cookies to apply to all subdomains of your domain, use \".yourdomain.com\" as the cookie domain).</p>"
						.'<ul>
							<li>
								<label for="tCookieDomain">Cookie Domain</label>
								<input id="tCookieDomain" type="text" name="CookieDomain" value="'.FormatStringForDisplay($CookieDomain, 1).'" />
							</li>
							<li>
								<label for="tCookiePath">Cookie Path</label>
								<input id="tCookiePath" type="text" name="CookiePath" value="'.FormatStringForDisplay($CookiePath, 1).'" />
							</li>
						</ul>
						<div class="Button"><input type="submit" value="Click here to complete the setup process!" /></div>
						</form>
					</fieldset>';
				} else {
					echo "<h2>Vanilla Installation Wizard (Complete)</h2>
					<p><strong>That's it! Vanilla is set up and ready to go.</strong></p>
					<p>Before you start inviting your friends in for discussions, there are a lot of other things you might want to set up. The #1 thing on your list should be getting some add-ons for Vanilla. With add-ons you can do all sorts of cool things like:</p>
					<ul>
						<li>Allow your users to save their common searches</li>
						<li>Change the language, layout, and style of your forum</li>
						<li>Add Atom and RSS feeds to your forum</li>
						<li>Allow users to quote other users in discussions</li>
					</ul>
					<p>All of these extensions (and a lot more) are available in <a href=\"http://lussumo.com/addons/\" target=\"Lussumo\">the Vanilla Add-on directory</a>.</p>
					<p>You'll also want to fine-tune your application settings, like:</p>
					<ul>
						<li>Change the number of discussions or comments per page</li>
						<li>Allow the public to browse your forum without an account</li>
						<li>Create new roles with various different permissions</li>
						<li>Create new categories, and even limit which roles get to access them</li>
					</ul>
					<p>All of these configuration options (and many more) are available in the settings tab of your Vanilla forum.</p>
					
					<p>If you need some help getting started with administering your new Vanilla forum, you can <a href=\"http://lussumo.com/docs\" target=\"Lussumo\">read the complete documentation</a> or ask for help on the <a href=\"http://lussumo.com/community/\" target=\"Lussumo\">Lussumo Community Forum</a>. Enough talking...</p>
					<div class=\"Button\"><a href=\"../people.php\">Go sign in and have some fun!</a></div>";
				}
				?>
			</div>
		</div>
   </body>
</html>