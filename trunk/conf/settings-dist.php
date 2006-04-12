<?php
/*
 Note: this is a "-dist" (distribution) file. You must create a copy of this
 file without the "-dist" in the filename for your installation. This way when
 you perform upgrades of Vanilla, your copy of the -dist file will not be
 overwritten.
 
 Copyright 2003 - 2005 Mark O'Sullivan
 This file is part of Vanilla.
 Vanilla is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.
 Vanilla is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.
 You should have received a copy of the GNU General Public License along with Vanilla; if not, write to the Free Software Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 The latest source code for Vanilla is available at www.lussumo.com
 Contact Mark O'Sullivan at mark [at] lussumo [dot] com

 Description: Create a copy of this file and name it settings.php. Use that file
 to specify your own custom information.
*/

// Path Settings
$Configuration['APPLICATION_PATH'] = '/path/to/vanilla/'; 
$Configuration['DATABASE_PATH'] = $Configuration['APPLICATION_PATH'] . 'conf/database.php'; 
$Configuration['LIBRARY_PATH'] = $Configuration['APPLICATION_PATH'] . 'library/'; 
$Configuration['EXTENSIONS_PATH'] = $Configuration['APPLICATION_PATH'] . 'extensions/'; 
$Configuration['LANGUAGES_PATH'] = $Configuration['APPLICATION_PATH'] . 'languages/';
$Configuration['THEME_PATH'] = $Configuration['APPLICATION_PATH'] . 'themes/vanilla/';

// People Settings
$Configuration['COOKIE_DOMAIN'] = '.yourdomain.com'; 
$Configuration['COOKIE_PATH'] = '/'; 
$Configuration['SUPPORT_EMAIL'] = 'support@yourdomain.com'; 

// Extension Configuration Parameters
$Configuration['PERMISSION_DATABASE_CLEANUP'] = '0';
?>