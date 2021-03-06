<?php
/**
 * @file
 * Provides ExternalModule class for User Profile module.
 */

namespace UserProfile\ExternalModule;

require_once 'UserProfile.php';

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;
use UserProfile\UserProfile;
use Project;
use UserRights;
use RCView;

/**
 * ExternalModule class for User Profile module.
 */
class ExternalModule extends AbstractExternalModule {
    protected $projectId;
    protected $usernameField;

    /**
     * @inheritdoc
     */
    function __construct() {
        parent::__construct();

        $this->projectId = ExternalModules::getSystemSetting('redcap_user_profile', 'project_id');
        $this->usernameField = ExternalModules::getSystemSetting('redcap_user_profile', 'username_field');
    }

    /**
     * @inheritdoc
     */
    function hook_every_page_top($project_id) {
        // Initializing User Profile JS settings variable.
        echo '<script>var userProfile = {};</script>';

        if (strpos(PAGE, 'ExternalModules/manager/control_center.php') !== false) {
            // Making sure the module is enabled for all projects.
            // TODO: move it to redcap_module_system_enable as soon as this hook
            // is released on REDCap.
            $this->setSystemSetting(ExternalModules::KEY_ENABLED, true);

            // Adding script and style.
            $this->includeJs('js/config.js');
            $this->includeCss('css/config.css');
            $this->setJsSetting('modulePrefix', $this->PREFIX);

            return;
        }

        if (!$this->projectId) {
            return;
        }

        if (PAGE == 'DataEntry/index.php') {
            if (!empty($_GET['user_profile_username']) && $project_id == $this->projectId) {
                global $Proj;

                // Setting default value for username when users get redirected
                // from "Create user profile" button.
                $Proj->metadata[$this->usernameField]['misc'] .= ' @DEFAULT="' . $_GET['user_profile_username'] . '"';
            }

            return;
        }

        if (PAGE != 'ControlCenter/view_users.php') {
            return;
        }

        $project = new Project($this->projectId);
        $settings = array(
            'nextProfileId' => $this->getAutoId(),
            'existingProfiles' => array(),
            'url' => APP_PATH_WEBROOT . 'DataEntry/index.php?pid=' . $this->projectId . '&event_id=' . $project->firstEventId . '&page=' . $project->firstForm,
        );

        foreach (UserProfile::getProfiles() as $username => $user_profile) {
            $settings['existingProfiles'][$username] = $user_profile->getProfileId();
        }

        $buttons = array(
            'addButton' => array(
                'icon' => 'user_add3',
                'label' => 'Create user profile',
            ),
            'editButton' => array(
                'icon' => 'user_edit',
                'label' => 'Edit user profile',
            ),
        );

        foreach ($buttons as $key => $btn_info) {
            $button = RCView::img(array('src' => APP_PATH_IMAGES . $btn_info['icon'] . '.png'));
            $button .= RCView::span(array(), $btn_info['label']);
            $button = RCView::button(array('id' => 'user-profile-btn', 'type' => 'button'), $button);

            $settings[$key] = $button;
        }

        $this->setJsSetting('addEditButtons', $settings);
        $this->includeJs('js/add_edit_buttons.js');
        $this->includeCss('css/add_edit_buttons.css');
    }

    /**
     * Adapted version of getAutoId() for callers outside this project's scope.
     *
     * @return int
     *   The new profile ID.
     *
     * @see getAutoId()
     */
    function getAutoId() {
        $Proj = new Project($this->projectId);
        $user_rights = UserRights::getPrivileges($this->projectId, USERID);

        // User is in a DAG, so only pull records from this DAG
        if (isset($user_rights['group_id']) && $user_rights['group_id'] != "")
        {
                $sql = "select distinct(substring(a.record,".(strlen($user_rights['group_id'])+2).")) as record
                                from redcap_data a left join redcap_data b
                                on a.project_id = b.project_id and a.record = b.record and b.field_name = '__GROUPID__'
                                where a.record like '{$user_rights['group_id']}-%' and a.field_name = '{$Proj->table_pk}'
                                and a.project_id = " . $this->projectId;
                $recs = db_query($sql);
        }
        // User is not in a DAG
        else {
                $sql = "select distinct record from redcap_data where project_id = " . $this->projectId . " and field_name = '{$Proj->table_pk}'";
                $recs = db_query($sql);
        }
        //Use query from above and find the largest record id and add 1
        $holder = 0;
        while ($row = db_fetch_assoc($recs))
        {
                if (is_numeric($row['record']) && is_int($row['record'] + 0) && $row['record'] > $holder)
                {
                        $holder = $row['record'];
                }
        }
        db_free_result($recs);
        // Increment the highest value by 1 to get the new value
        $holder++;
        //If user is in a DAG append DAGid+dash to beginning of record
        if (isset($user_rights['group_id']) && $user_rights['group_id'] != "")
        {
                $holder = $user_rights['group_id'] . "-" . $holder;
        }
        // Return new auto id value
        return $holder;
    }

    /**
     * Includes a local JS file.
     *
     * @param string $path
     *   The relative path to the js file.
     */
    protected function includeJs($path) {
        echo '<script src="' . $this->getUrl($path) . '"></script>';
    }

    /**
     * Includes a local CSS file.
     *
     * @param string $path
     *   The relative path to the css file.
     */
    protected function includeCss($path) {
        echo '<link rel="stylesheet" href="' . $this->getUrl($path) . '">';
    }

    /**
     * Sets a JS setting.
     *
     * @param string $key
     *   The setting key to be appended to the module settings object.
     * @param mixed $value
     *   The setting value.
     */
    protected function setJsSetting($key, $value) {
        echo '<script>userProfile.' . $key . ' = ' . json_encode($value) . ';</script>';
    }
}
