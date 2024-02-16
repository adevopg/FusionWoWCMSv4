<?php

/**
 * @package FusionCMS
 * @author  Jesper Lindström
 * @author  Xavier Geerinck
 * @author  Elliott Robbins
 * @link    http://fusion-hub.com
 */

class Acl_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();

        if (file_exists("application/config/owner.php")) {
            $this->load->config('owner');
            $group = $this->config->item('default_owner_group');
            $id = $this->user->getId($this->config->item('owner'));

            if (!$id) {
                show_error("The owner account that was specified during the installation does not exist. Please reinstall FusionCMS.");
            }

            $this->assignGroupToUser($group, $id);

            unlink("application/config/owner.php");
        }
    }

    /**
     * Get the roles for a group by the user ID
     *
     * @param  Int $userId
     * @param  String $moduleName
     * @return Array
     */
    public function getGroupRolesByUser($userId, $moduleName = false)
    {
        $this->db->select("agr.role_name, agr.module");
        $this->db->where("aag.account_id", $userId);
        $this->db->where("aag.group_id = agr.group_id");

        if ($moduleName) {
            $this->db->where("agr.module", $moduleName);
        }

        $query = $this->db->get("acl_group_roles agr, acl_account_groups aag");

        if ($query->num_rows()) {
            return $query->result_array();
        } else {
            return false;
        }
    }

    /**
     * Get account roles permissions
     *
     * @param  int $userId
     * @param  int $default_group
     * @return array
     */
    public function getAccountRolesPermissions(int $userId = 0, int $default_group = 1)
    {
        // Query: Prepare
        $query = $this->db->select('agr.module')
                          ->select('agr.role_name')

                          # Filter by account id
                          ->where('aag.account_id', $userId)

                          # Filter by group
                          ->group_start()
                          ->where('aag.group_id = agr.group_id')
                          ->or_where('agr.group_id', $default_group)
                          ->group_end()

                          # Distinct
                          ->distinct()

                          # Get
                          ->get('acl_group_roles agr, acl_account_groups aag');

        // Query: Make sure we have results
        if($query && $query->num_rows())
            return $query->result_array();

        return [];
    }

    /**
     * Get the account-specific permissions
     *
     * @param  Int $userId
     * @return Array
     */
    public function getAccountPermissions($userId)
    {
        $this->db->select("account_id, permission_name, module, value");
        $this->db->where("account_id", $userId);
        $query = $this->db->get("acl_account_permissions");

        if ($query->num_rows() > 0) {
            return $query->result_array();
        } else {
            return false;
        }
    }

    /**
     * Get the account-specific roles
     *
     * @param  Int $userId
     * @param  String $module
     * @return Array
     */
    public function getAccountRoles($userId, $module = false)
    {
        $this->db->select("role_name");
        $this->db->where("account_id", $userId);

        if ($module) {
            $this->db->where("module", $module);
        }

        $query = $this->db->get("acl_account_roles");

        if ($query->num_rows() > 0) {
            return $query->result_array();
        } else {
            return false;
        }
    }

    /**
     * Get the groups of the given user
     *
     * @param  $accountId
     * @return Array
     */
    public function getGroupsByUser($accountId = false)
    {
        if (!$accountId) {
            $accountId = $this->user->getId();
        }

        $this->db->select("ag.id, ag.priority, ag.name, ag.color, ag.color");
        $this->db->where("aag.account_id", $accountId);
        $this->db->where("aag.group_id = ag.id");
        $this->db->order_by("ag.priority", "DESC");
        $query = $this->db->get("acl_account_groups aag, acl_groups ag");

        if ($query->num_rows() > 0) {
            return $query->result_array();
        } else {
            // No group found; default to player
            return array($this->getGroup($this->config->item('default_player_group')));
        }
    }

    /**
     * Get all the groups
     *
     * @return Array
     */
    public function getGroups()
    {
        $this->db->select('ag.id, ag.priority, ag.name, ag.color, ag.description');
        $query = $this->db->get('acl_groups ag');

        if ($query->num_rows() > 0) {
            return $query->result_array();
        } else {
            return false;
        }
    }

    /**
     * Get member count of a group
     *
     * @param  Int $groupId
     * @return Int
     */
    public function getGroupMemberCount($id)
    {
        $query = $this->db->query("SELECT COUNT(*) `memberCount` FROM acl_account_groups WHERE group_id=?", array($id));

        if ($query->num_rows() > 0) {
            $result = $query->result_array();

            return $result[0]['memberCount'];
        } else {
            return 0;
        }
    }

    /*
     * Get the members of a group
     * @param Int $groupId
     * @return Array
     */
    public function getGroupMembers($id)
    {
        $query = $this->db->query("SELECT account_id FROM acl_account_groups WHERE group_id=?", array($id));

        if ($query->num_rows()) {
            $result = $query->result_array();

            foreach ($result as $k => $v) {
                $result[$k]['username'] = $this->user->getUsername($v['account_id']);
            }

            return $result;
        } else {
            return false;
        }
    }

    /**
     * Get the group by the given id.
     *
     * @param  $groupId
     * @return Array
     */
    public function getGroup($groupId)
    {
        $this->db->select('id, priority, name, color, description');
        $this->db->where('id', $groupId);
        $query = $this->db->get('acl_groups');

        if ($query->num_rows() > 0) {
            $result = $query->result_array();

            return $result[0];
        } else {
            return false;
        }
    }

    /**
     * Get the group by the given name
     *
     * @param  $groupName
     * @return Boolean
     */
    public function getGroupByName($groupName)
    {
        $this->db->select('id, priority, name, color, description');
        $this->db->where('name', $groupName);
        $query = $this->db->get('acl_groups');

        if ($query->num_rows() > 0) {
            $result = $query->result_array();

            return $result[0];
        } else {
            return false;
        }
    }

    /**
     * Check if a group has a specific role
     *
     * @param  Int $id
     * @param  String $name
     * @param  String $module
     * @return Boolean
     */
    public function groupHasRole($groupId, $name, $module)
    {
        $query = $this->db->query("SELECT COUNT(*) `total` FROM acl_group_roles WHERE role_name=? AND module=? AND group_id=?", array($name, $module, $groupId));

        if ($query->num_rows()) {
            $result = $query->result_array();

            return $result[0]['total'];
        } else {
            return false;
        }
    }

    /**
     * Get the database roles for a module
     *
     * @param  String $moduleName
     * @return Array
     */
    public function getRolesByModule($moduleName, $groupId)
    {
        $query = $this->db->query("SELECT * FROM acl_group_roles WHERE module = ? AND group_id = ?", [$moduleName, $groupId]);

        if ($query->num_rows() > 0) {
            return $query->result_array();
        } else {
            return false;
        }
    }

    /**
     * Create a group
     *
     * @param $data
     * @return int
     */
    public function createGroup($data)
    {
        $this->db->insert('acl_groups', $data);

        return $this->db->insert_id();
    }

    /**
     * Delete the group with the given id
     *
     * @param Int $groupId
     */
    public function deleteGroup($groupId)
    {
        $this->db->delete('acl_groups', array('id' => $groupId));
    }

    /**
     * Assign a group to a user
     *
     * @param Int $groupId
     * @param Int $accountId
     */
    public function assignGroupToUser($groupId, $accountId)
    {
        $data = array(
            "account_id" => $accountId,
            "group_id" => $groupId
        );

        $this->db->insert('acl_account_groups', $data);
    }

    /**
     * Remove a group assignment
     *
     * @param Int $groupId
     * @param Int $accountId
     */
    public function removeGroupFromUser($groupId, $accountId)
    {
        $data = array(
            "account_id" => $accountId,
            "group_id" => $groupId
        );

        $this->db->delete('acl_account_groups', $data);
    }

    /**
     * Remove all group assignments
     *
     * @param Int $accountId
     */
    public function removeGroupsFromUser($accountId)
    {
        $data = array(
            "account_id" => $accountId
        );

        $this->db->delete('acl_account_groups', $data);
    }

    /**
     * Remove all permission assignments
     *
     * @param Int $accountId
     */
    public function removePermissionsFromUser($accountId)
    {
        $data = array(
            "account_id" => $accountId
        );

        $this->db->delete('acl_account_permissions', $data);
    }

    /**
     * Assign a permission to a user
     *
     * @param Int $accountId
     * @param String $permissionName
     * @param string $moduleName
     */
    public function assignPermissionToUser($accontId, $permissionName, $moduleName, $value)
    {
        $data = array(
            "account_id" => $accontId,
            "permission_name" => $permissionName,
            "module" => $moduleName,
            "value" => $value
        );

        $this->db->insert("acl_account_permissions", $data);
    }

    /**
     * Add a role to the given group
     *
     * @param Int $groupId
     * @param String $name
     * @param String $module
     */
    public function addRoleToGroup($groupId, $name, $module)
    {
        $data = array(
            'group_id' => $groupId,
            'role_name' => $name,
            'module' => $module
        );

        $this->db->insert('acl_group_roles', $data);
    }

    /**
     * Delete a role from the given group
     *
     * @param Int $groupId
     * @param String $name
     * @param String $module
     */
    public function deleteRoleFromGroup($groupId, $name, $module)
    {
        $this->db->delete('acl_group_roles', array('group_id' => $groupId, 'role_name' => $name, 'module' => $module));
    }

    /**
     * Save the group
     *
     * @param Int $id
     * @param Array $data
     */
    public function saveGroup($id, $data)
    {
        $this->db->where('id', $id);
        $this->db->update('acl_groups', $data);
    }

    /**
     * Delete all roles from a given group
     *
     * @param Int $groupId
     */
    public function deleteAllRoleFromGroup($groupId)
    {
        $this->db->delete('acl_group_roles', array('group_id' => $groupId));
    }
}
