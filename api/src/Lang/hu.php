<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

// default language for translations

// strings only used in setup
$langSetup["dbconnectfailed"] = "Database connection failed.";
$langSetup["serversaid"] = "Server said: %s"; // substitutes server error message
$langSetup["checkdbsettings"] = "Please check database settings in 'config.php' file.";
$langSetup["dbqueryfailed"] = "Database query failed.";
$langSetup["dbtablessuccess"] = "Database tables successfully created!";
$langSetup["setupuser"] = "Now please set up your µlogger user.";
$langSetup["congratulations"] = "Congratulations!";
$langSetup["setupcomplete"] = "Setup is now complete. You may go to the <a href=\"../index.php\">main page</a> now and log in with your new user account.";
$langSetup["disablewarn"] = "IMPORTANT! YOU MUST DISABLE 'setup.php' SCRIPT OR REMOVE IT FROM YOUR SERVER.";
$langSetup["disabledesc"] = "Leaving the script accessible from browser is a major security risk. Anybody will be able to run it, delete your database and set up new user account. Delete the file or disable it by setting %s value back to %s."; // substitutes variable name and value
$langSetup["setupfailed"] = "Unfortunately something has gone wrong. You may try to find more info in your webserver logs.";
$langSetup["welcome"] = "Welcome to µlogger!";
$langSetup["disabledwarn"] = "For security reasons this script is disabled by default. To enable it you must edit 'scripts/setup.php' file in text editor and set %s variable at the beginning of the file to %s."; // substitutes variable name and value
$langSetup["lineshouldread"] = "Line: %s should read: %s";
$langSetup["dorestart"] = "Please restart this script when you are done.";
$langSetup["createconfig"] = "Please create 'config.php' file in root folder. You may start by copying it from 'config.default.php'. Make sure that you adjust config values to match your needs and your database setup.";
$langSetup["nodbsettings"] = "You must provide your database credentials in 'config.php' file (%s)."; // substitutes variable names
$langSetup["scriptdesc"] = "This script will set up tables needed for µlogger (%s). They will be created in your database named %s. Warning, if the tables already exist they will be dropped and recreated, their content will be destroyed."; // substitutes table names and db name
$langSetup["scriptdesc2"] = "When done the script will ask you to provide user name and password for your µlogger user.";
$langSetup["startbutton"] = "Press to start";
$langSetup["restartbutton"] = "Restart";
$langSetup["optionwarn"] = "PHP configuration option %s must be set to %s."; // substitutes option name and value
$langSetup["extensionwarn"] = "Required PHP extension %s is not available."; // substitutes extension name
$langSetup["notwritable"] = "Folder '%s' must be writable by PHP."; // substitutes folder path


// application strings
$lang["title"] = "• μlogger •";
$lang["private"] = "Felhasználónév és jelszó szükséges a belépéshez";
$lang["authfail"] = "Hibás név vagy jelszó";
$lang["user"] = "Felhasználó";
$lang["track"] = "Útvonal";
$lang["latest"] = "Utolsó rögzített pont";
$lang["autoreload"] = "Automatikus frissítés";
$lang["reload"] = "Frissítés most";
$lang["export"] = "Adatok letöltése";
$lang["chart"] = "Magasság diagramm";
$lang["close"] = "Bezár";
$lang["time"] = "Rögzítés ideje";
$lang["speed"] = "Sebesség";
$lang["accuracy"] = "Pontosság";
$lang["position"] = "Position";
$lang["altitude"] = "Magasság";
$lang["bearing"] = "Bearing";
$lang["ttime"] = "Menetidő";
$lang["aspeed"] = "Átlagsebesség";
$lang["tdistance"] = "Megtett út";
$lang["pointof"] = "Rögzített pontok száma %d / %d"; // e.g. Point 3 of 10
$lang["summary"] = "Utazás adatai";
$lang["suser"] = "Felhasználónév";
$lang["logout"] = "Kilépés";
$lang["login"] = "Belépés";
$lang["username"] = "Felhasználó";
$lang["password"] = "Jelszó";
$lang["language"] = "Nyelv";
$lang["newinterval"] = "Automatikus frissítés ideje (másodpercben)";
$lang["api"] = "Map API";
$lang["units"] = "Mértékegység";
$lang["metric"] = "Metrikus";
$lang["imperial"] = "Imperal/US";
$lang["nautical"] = "Nautical";
$lang["admin"] = "Administrator";
$lang["adminmenu"] = "Adminisztráció";
$lang["passwordrepeat"] = "Repeat password";
$lang["passwordenter"] = "Enter password";
$lang["usernameenter"] = "Enter username";
$lang["adduser"] = "Add user";
$lang["userexists"] = "User exists";
$lang["cancel"] ="Cancel";
$lang["submit"] = "Submit";
$lang["oldpassword"] = "Old password";
$lang["newpassword"] = "New password";
$lang["newpasswordrepeat"] = "Repeat new password";
$lang["changepass"] = "Change password";
$lang["gps"] = "GPS";
$lang["network"] = "Network";
$lang["deluser"] = "Remove user";
$lang["edituser"] = "Edit user";
$lang["servererror"] = "Server error";
$lang["allrequired"] = "All fields are required";
$lang["passnotmatch"] = "Passwords don't match";
$lang["oldpassinvalid"] = "Wrong old password";
$lang["passempty"] = "Empty password";
$lang["loginempty"] = "Empty login";
$lang["passstrengthwarn"] = "Invalid password strength";
$lang["actionsuccess"] = "Action completed successfully";
$lang["actionfailure"] = "Something went wrong";
$lang["notauthorized"] = "User not authorized";
$lang["userunknown"] = "User unknown";
$lang["userdelwarn"] = "Warning!\n\nYou are going to permanently delete user %s, together with all their routes and positions.\n\nAre you sure?"; // substitutes user login
$lang["editinguser"] = "You are editing user %s"; // substitutes user login
$lang["selfeditwarn"] = "Your can't edit your own user with this tool";
$lang["apifailure"] = "Sorry, can't load %s API"; // substitutes api name (gmaps or openlayers)
$lang["trackdelwarn"] = "Warning!\n\nYou are going to permanently delete track %s and all its positions.\n\nAre you sure?"; // substitutes track name
$lang["editingtrack"] = "You are editing track %s"; // substitutes track name
$lang["deltrack"] = "Remove track";
$lang["trackname"] = "Track name";
$lang["edittrack"] = "Edit track";
$lang["positiondelwarn"] = "Warning!\n\nYou are going to permanently delete position %d of track %s.\n\nAre you sure?"; // substitutes position index and track name
$lang["editingposition"] = "You are editing position #%d of track %s"; // substitutes position index and track name
$lang["delposition"] = "Remove position";
$lang["delimage"] = "Remove image";
$lang["comment"] = "Comment";
$lang["image"] = "Image";
$lang["editposition"] = "Edit position";
$lang["passlenmin"] = "Password must be at least %d characters"; // substitutes password minimum length
$lang["passrules_1"] = "It should contain at least one lower case letter, one upper case letter";
$lang["passrules_2"] = "It should contain at least one lower case letter, one upper case letter and one digit";
$lang["passrules_3"] = "It should contain at least one lower case letter, one upper case letter, one digit and one non-alphanumeric character";
$lang["owntrackswarn"] = "Your can only edit your own tracks";
$lang["gmauthfailure"] = "There may be problem with Google Maps API key on this page";
$lang["gmapilink"] = "You may find more information about API keys on <a target=\"_blank\" href=\"https://developers.google.com/maps/documentation/javascript/get-api-key\">this Google webpage</a>";
$lang["import"] = "Import track";
$lang["iuploadfailure"] = "Uploading failed";
$lang["iparsefailure"] = "Parsing failed";
$lang["idatafailure"] = "No track data in imported file";
$lang["isizefailure"] = "The uploaded file size should not exceed %d bytes"; // substitutes number of bytes
$lang["imultiple"] = "Notice, multiple tracks imported (%d)"; // substitutes number of imported tracks
$lang["allusers"] = "All users";
$lang["unitday"] = "d"; // abbreviation for days, like 4 d 11:11:11
$lang["unitkmh"] = "km/h"; // kilometer per hour
$lang["unitm"] = "m"; // meter
$lang["unitamsl"] = "a.s.l."; // above mean see level
$lang["unitkm"] = "km"; // kilometer
$lang["unitmph"] = "mph"; // mile per hour
$lang["unitft"] = "ft"; // feet
$lang["unitmi"] = "mi"; // mile
$lang["unitkt"] = "kt"; // knot
$lang["unitnm"] = "nm"; // nautical mile
$lang["config"] = "Settings";
$lang["editingconfig"] = "Default application settings";
$lang["latitude"] = "Initial latitude";
$lang["longitude"] = "Initial longitude";
$lang["interval"] = "Interval (s)";
$lang["googlekey"] = "Google Maps API key";
$lang["passlength"] = "Minimum password length";
$lang["passstrength"] = "Minimum password strength";
$lang["requireauth"] = "Require authorization";
$lang["publictracks"] = "Public tracks";
$lang["strokeweight"] = "Stroke weight";
$lang["strokeopacity"] = "Stroke opacity";
$lang["strokecolor"] = "Stroke color";
$lang["colornormal"] = "Marker color";
$lang["colorstart"] = "Start marker color";
$lang["colorstop"] = "Stop marker color";
$lang["colorextra"] = "Extra marker color";
$lang["colorhilite"] = "Highlight marker color";
$lang["uploadmaxsize"] = "Maximum upload size (MB)";
$lang["ollayers"] = "OpenLayers layer";
$lang["layername"] = "Layer name";
$lang["layerurl"] = "Layer URL";
$lang["add"] = "Add";
$lang["edit"] = "Edit";
$lang["delete"] = "Delete";
$lang["settings"] = "Settings";
$lang["trackcolor"] = "Track color";
?>
