<?php
/**
 * Groups component
 * 
 * @author Pierre HUBERT
 */

class GroupsComponent {

    /**
     * Groups list table
     */
    const GROUPS_LIST_TABLE = DBprefix . "groups";

    /**
     * Groups members table
     */
    const GROUPS_MEMBERS_TABLE = DBprefix."groups_members";

    /**
     * Create a new group
     * 
     * @param NewGroup $newGroup Information about the new group
     * to create
     * @return int The ID of the created group / -1 in case of failure
     */
    public function create(NewGroup $newGroup) : int {

        //Insert the group in the database
        db()->addLine(self::GROUPS_LIST_TABLE, array(
            "time_create" => $newGroup->get_time_sent(),
            "userid_create" => $newGroup->get_userID(),
            "name" => $newGroup->get_name()
        ));

        //Get the ID of the last inserted group
        $groupID = db()->getLastInsertedID();

        //Check for errors
        if(!$groupID > 0)
            return -1;

        //Register the user who created the group as an admin of the group
        $member = new GroupMember;
        $member->set_group_id($groupID);
        $member->set_userID($newGroup->get_userID());
        $member->set_time_sent($newGroup->get_time_sent());
        $member->set_level(GroupMember::ADMINISTRATOR);
        $this->insertMember($member);

        return $groupID;
    }

    /**
     * Get and return information about a group
     * 
     * @param int $id The ID of the target group
     * @return GroupInfo Information about the group / invalid
     * object in case of failure
     */
    public function get_info(int $id) : GroupInfo {

        //Query the database
        $info = db()->select(self::GROUPS_LIST_TABLE, "WHERE id = ?", array($id));

        //Check for results
        if(count($info) == 0)
            return new GroupInfo(); //Return invalid object
        
        //Create and fill GroupInfo object with database entry
        return $this->dbToGroupInfo($info[0]);
    }

    /**
     * Get and return advanced information about a group
     * 
     * @param int $id The ID of the target group
     * @return GroupInfo Information about the group / invalid
     * object in case of failure
     */
    public function get_advanced_info(int $id) : AdvancedGroupInfo {

        //Query the database
        $info = db()->select(self::GROUPS_LIST_TABLE, "WHERE id = ?", array($id));

        //Check for results
        if(count($info) == 0)
            return new AdvancedGroupInfo(); //Return invalid object
        
        //Create and fill GroupInfo object with database entry
        return $this->dbToAdvancedGroupInfo($info[0]);
    }

    /**
     * Insert a new group member
     * 
     * @param GroupMember $member Information about the member to insert
     * @return bool TRUE for a success / FALSE else
     */
    private function insertMember(GroupMember $member) : bool {
        return db()->addLine(self::GROUPS_MEMBERS_TABLE, array(
            "groups_id" => $member->get_group_id(),
            "user_id" => $member->get_userID(),
            "time_create" => $member->get_time_sent(),
            "level" => $member->get_level()
        ));
    }

    /**
     * Check whether a user has already a saved membership in a group or not
     * 
     * @param int $userID The ID of the target user
     * @param int $groupID The ID of the target group
     * @return bool TRUE if the database includes a membership for the user / FALSE else
     */
    private function hasMembership(int $userID, int $groupID) : bool {
        return db()->count(
            self::GROUPS_MEMBERS_TABLE,
            "WHERE groups_id = ? AND user_id = ?", 
            array($groupID, $userID)) > 0;
    }

    /**
     * Get the membership level of a user to a group
     * 
     * @param int $userID The ID of the queried user
     * @param int $groupID The ID of the target group
     * @return int The membership level of the user
     */
    public function getMembershipLevel(int $userID, int $groupID) : int {

        //Check for membership
        if(!$this->hasMembership($userID, $groupID))
            return GroupMember::VISITOR;
        
        //Fetch the database to get membership
        $results = db()->select(
            self::GROUPS_MEMBERS_TABLE,
            "WHERE groups_id = ? AND user_id = ?",
            array($groupID, $userID),
            array("level")
        );

        //Check for results
        if(count($results) < 0)
            return GroupMember::VISITOR; //Security first
        
        return $results[0]["level"];
    }

    /**
     * Count the number of members of a group
     * 
     * @param int $id The ID of the target group
     * @return int The number of members of the group
     */
    private function countMembers(int $id) : int {
        return db()->count(self::GROUPS_MEMBERS_TABLE, 
            "WHERE groups_id = ?",
            array($id));
    }

    /**
     * Turn a database entry into a GroupInfo object
     * 
     * @param array $data Database entry
     * @param GroupInfo $group The object to fill with the information (optionnal)
     * @return GroupInfo Generated object
     */
    private function dbToGroupInfo(array $data, GroupInfo $info = null) : GroupInfo {

        if($info == null)
            $info = new GroupInfo();

        $info->set_id($data["id"]);
        $info->set_name($data["name"]);
        $info->set_number_members($this->countMembers($info->get_id()));
        $info->set_membership_level($this->getMembershipLevel(userID, $info->get_id()));

        return $info;

    }

    /**
     * Turn a database entry into AdvancedGroupInfo object entry
     * 
     * @param array $data Database entry
     * @return AdvancedGroupInfo Advanced information about the group
     */
    private function dbToAdvancedGroupInfo(array $data) : AdvancedGroupInfo {

        //Parse basical information about the group
        $info = new AdvancedGroupInfo();
        $this->dbToGroupInfo($data, $info);

        //Parse advanced information
        $info->set_time_create($data["time_create"]);

        return $info;

    }
}

//Register component
Components::register("groups", new GroupsComponent());