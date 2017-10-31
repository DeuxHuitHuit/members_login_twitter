<?php
/**
 * Copyright: Deux Huit Huit 2017
 * License: MIT, see the LICENSE file
 */

if (!defined("__IN_SYMPHONY__")) die("<h2>Error</h2><p>You cannot directly access this file</p>");

class extension_members_login_twitter extends Extension
{
    /**
     * Name of the extension
     * @var string
     */
    const EXT_NAME = 'Members: Twitter Login';

    /* ********* INSTALL/UPDATE/UNISTALL ******* */

    protected function checkDependency($depname)
    {
        $status = ExtensionManager::fetchStatus(array('handle' => $depname));
        $status = current($status);
        if ($status != EXTENSION_ENABLED) {
            Administration::instance()->Page->pageAlert("Could not load `$depname` extension.", Alert::ERROR);
            return false;
        }
        return true;
    }

    protected function checkDependencyVersion($depname, $version)
    {
        $installedVersion = ExtensionManager::fetchInstalledVersion($depname);
        if (version_compare($installedVersion, $version) == -1) {
            Administration::instance()->Page->pageAlert("Extension `$depname` must have version $version or newer.", Alert::ERROR);
            return false;
        }
        return true;
    }

    /**
     * Creates the table needed for the settings of the field
     */
    public function install()
    {
        // depends on "members"
        if (!$this->checkDependencyVersion('members', '1.9.0')) {
            return false;
        }
        return true;
    }
    
    /**
     * Creates the table needed for the settings of the field
     */
    public function update($previousVersion = false)
    {
        $ret = true;
        return $ret;
    }

    /**
     *
     * Drops the table needed for the settings of the field
     */
    public function uninstall()
    {
        return true;
    }

    /*------------------------------------------------------------------------------------------------*/
    /*  Delegates  */
    /*------------------------------------------------------------------------------------------------*/

    public function getSubscribedDelegates()
    {
        return array(
            array(
                'page'     => '/frontend/',
                'delegate' => 'MembersLogin',
                'callback' => 'membersLogin'
            ),
        );
    }

    public function membersLogin(array $context)
    {
        if ($context['is-logged-in']) {
            return;
        }
        if ($_SESSION['OAUTH_SERVICE'] !== 'twitter') {
            return;
        }
        if (empty($_SESSION['OAUTH_MEMBER_ID'])) {
            return;
        }
        $context['is-logged-in'] = $_SESSION['OAUTH_TIMESTAMP'] + TWO_WEEKS > time();
        $context['member_id'] = $_SESSION['OAUTH_MEMBER_ID'];
    }
}
