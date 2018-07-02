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

}

//Register component
Components::register("groups", new GroupsComponent());