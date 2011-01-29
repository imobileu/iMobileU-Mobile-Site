<?php
/**
  * @package Authentication
  */

/**
  * This authority uses a passwd style file
  * @package Authentication
  */
class PasswdAuthentication extends AuthenticationAuthority
{
    private $userFile;
    private $groupFile;
    private $users = array();
    private $userEmails = array();
    private $groups = array();
    private $groupGIDs = array();
    
    protected function validUserLogins()
    {
        return array('FORM', 'NONE');
    }
    
    private function loadUserData()
    {
        if ($this->users) {
            return;
        }
        
        $data = file_get_contents($this->userFile);
        $lines = explode(PHP_EOL, $data);
        $this->users = array();
        $this->userEmails = array();
        foreach ($lines as $line) {
            if ($user = $this->parseUserLine($line)) {
                $this->users[$user['userID']] = $user;
                if ($user['email']) {
                    $this->userEmails[$user['userID']] = $user['email'];
                }

            }
        }
    }
    
    private function parseUserLine($line)
    {
        $line = trim($line);

        // ignore blank lines or lines with pound symbol first (comments)
        if (strlen($line)==0 || preg_match("/^\s*#/", $line)) {
            return false;
        }

        $fields = explode(":", $line);
        $user = array(
            'userID'=>'',
            'email'=>'',
            'md5'=>'',
            'fullname'=>''
        );
        switch (count($fields))
        {
            case 4:
                $user['fullname'] = trim($fields[3]);
            case 3:
                $user['email']=Validator::isValidEmail(trim($fields[2])) ? trim($fields[2]) : '';
            case 2:
                $user['userID']=trim($fields[0]);
                $user['md5']=trim($fields[1]);
                break;
            default:
                return false;
        }        

        /* some quick validation */        
        if (strlen($user['userID'])==0 | !preg_match("/^[a-f0-9]{32}$/", $user['md5'])) {
            error_log("Invalid user line: $line");
            $user = false;
        }
        
        return $user;
    }

    private function loadGroupData()
    {
        if ($this->groups) {
            return;
        }
        
        if (!is_readable($this->groupFile)) {
            throw new Exception("Unable to load group file $this->groupFile");
        }
        
        $data = file_get_contents($this->groupFile);
        $lines = explode(PHP_EOL, $data);
        $this->groups = array();
        $this->groupsByGID = array();
        foreach ($lines as $line) {
            if ($group = $this->parseGroupLine($line)) {
                $this->groups[$group['group']] = $group;
                $this->groupsGIDS[$group['group']] = $group['gid'];
            }
        }
    }
    
    private function parseGroupLine($line)
    {
        $line = trim($line);

        // ignore blank lines or lines with pound symbol first (comments)
        if (strlen($line)==0 || preg_match("/^\s*#/", $line)) {
            return false;
        }

        $fields = explode(":", $line);
        if (!count($fields)==3) {
            return false;
        }
        
        $group = array(
            'group'=>trim($fields[0]),
            'gid'=>trim($fields[1]),
            'members'=>array_map('trim', explode(",",trim($fields[2])))
        );
        
        if (strlen($group['group'])==0 || strlen($group['gid'])==0) {
            error_log("Invalid group line: $line");
            $group = false;
        }

        return $group;
    }
    
    public function auth($login, $password, &$user)
    {
        if ($this->userLogin == 'NONE') {
            return AUTH_FAILED;
        }
        
        if ($userData = $this->getUserData($login)) {
            if (md5($password) == $userData['md5']) {
                $user = $this->getUser($login);
                return AUTH_OK;
            } else {
                return AUTH_FAILED;
            }
        } else {
            return AUTH_USER_NOT_FOUND;
        }
    }
    
    /*
     * Retrieves the user array by userID or email
     * @param string $login a userID or email address
     * @return array an array of userData or false if the user could not be found
     */
    private function getUserData($login)
    {
        if (strlen($login)==0) {
            return false;
        }
        
        $this->loadUserData();
        
        if (isset($this->users[$login])) {
            return $this->users[$login];
        }
        
        if (Validator::isValidEmail($login) && (($userID = array_search($login, $this->userEmails)) !== false)) {
            return $this->getUserData($userID);
        } 
        
        return false;
    }

    public function getUser($login)
    {
        if ($this->userLogin == 'NONE') {
            return false;
        }

        if (strlen($login)==0) {
            return new AnonymousUser();       
        }

        if ($userData = $this->getUserData($login)) {
            $user = new BasicUser($this);
            $user->setUserID($userData['userID']);
            $user->setEmail($userData['email']);
            $user->setFullName($userData['fullname']);
            return $user;
        } else {
            return false;
            return AUTH_USER_NOT_FOUND; // not sure which one is correct yet
        }
    }

    private function getGroupData($group)
    {
        if (strlen($group)==0) {
            return false;
        }
        
        $this->loadGroupData();
        
        if (isset($this->groups[$group])) {
            return $this->groups[$group];
        }
        
        if (($group = array_search($group, $this->groupGIDs) !== false)) {
            return $this->getGroupData($group);
        } 
        
        return false;
    }

    public function getGroup($group)
    {
        if (strlen($group)==0) {
            return false;
        }
        
        if ($groupData = $this->getGroupData($group)) {

            $group = new BasicUserGroup($this);
            $group->setGroupID($groupData['gid']);
            $group->setGroupName($groupData['group']);
            $members = is_array($groupData['members']) ? $groupData['members'] : array();
            
            $groupMembers = array();

            //translate the user strings into user objects            
            foreach ($members as $user) {
                //parse the authority|user pair. If no authority is indicated than use this authority's index
                $userPieces = explode("|", $user);
                $authorityIndex = count($userPieces)==2 ? $userPieces[0] : $this->getAuthorityIndex();
                $userID = count($userPieces)==2 ? $userPieces[1] : $userPieces[0];
                if ($authority = AuthenticationAuthority::getAuthenticationAuthority($authorityIndex)) {
                    if ($user = $authority->getUser($userID)) {
                        $groupMembers[] = $user;
                    }
                } else {
                    throw new Exception("Invalid authority $authorityIndex when parsing group information for $group");
                }
            }
            $group->setMembers($groupMembers);
            return $group;
        } else {
            return false;
        }
    }

    public function init($args)
    {
        parent::init($args);
        $args = is_array($args) ? $args : array();
        $this->userFile = isset($args['USER_FILE']) ? $args['USER_FILE'] : null;
        $this->groupFile = isset($args['GROUP_FILE']) ? $args['GROUP_FILE'] : null;
        
        if ($this->userLogin != 'NONE') {        
            if (!is_readable($this->userFile)) {
                throw new Exception("Unable to load password file $this->userFile");
            }
        }
    }
}
