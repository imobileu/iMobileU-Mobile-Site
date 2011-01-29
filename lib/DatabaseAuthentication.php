<?php
/**
 * Database Authentication
 * @package Authentication
 */

/**
 * Authentication Authority based on values in a database
 * @see db
 * @package Authentication
 */
class DatabaseAuthentication extends AuthenticationAuthority
{
    protected $connection;
    protected $tableMap=array();
    protected $fieldMap=array();
    protected $hashAlgo='md5';
    protected $hashSalt='';

    protected function validUserLogins()
    {
        return array('FORM', 'NONE');
    }

    public function auth($login, $password, &$user)
    {
        $sql = sprintf("SELECT `%s` FROM `%s` WHERE (`%s`=? OR `%s`=?)", $this->getField('user_password'), $this->getTable('user'), $this->getField('user_userid'), $this->getField('user_email'));
        $result = $this->connection->query($sql, array($login, $login));
        if ($row = $result->fetch()) {
            if (hash($this->hashAlgo, $this->hashSalt . $password) == $row[$this->getField('user_password')]) {
                $user = $this->getUser($login);
                return AUTH_OK;
            } else {
                return AUTH_FAILED;
            }
        } else {
            return AUTH_USER_NOT_FOUND;
        }
    }

    public function getUser($login)
    {
        if (empty($login)) {
            return new AnonymousUser();       
        }

        $sql = sprintf("SELECT * FROM `%s` WHERE (`%s`=? or `%s`=?)", $this->getTable('user'), $this->getField('user_userid'), $this->getField('user_email'));
        $result = $this->connection->query($sql, array($login, $login));
        if ($row = $result->fetch()) {
            $user = new DatabaseUser($this);
            $user->setUserID($row[$this->getField('user_userid')]);
            $user->setEmail($row[$this->getField('user_email')]);
            if (isset($row[$this->getField('user_fullname')])) {
                $user->setFullName($row[$this->getField('user_fullname')]);
            }
            if (isset($row[$this->getField('user_firstname')])) {
                $user->setFirstName($row[$this->getField('user_firstname')]) ;
            }
            if (isset($row[$this->getField('user_lastname')])) {
                $user->setLastName($row[$this->getField('user_lastname')]) ;
            }

            return $user;
        } else {
            return false;
        }
    }

    public function getGroup($group)
    {
        if (strlen($group)==0) {
            return false;
        }

        $sql = sprintf("SELECT * FROM `%s` WHERE `%s`=?", $this->getTable('group'), $this->getField('group_groupname'));
        $result = $this->connection->query($sql, array($group));
        if ($row = $result->fetch()) {
            $group = new DatabaseUserGroup($this);
            $group->setGroupID($row[$this->getField('group_gid')]);
            $group->setGroupName($row[$this->getField('group_groupname')]);
            return $group;
        } else {
            return false;
        }
    }

    public function init($args)
    {
        parent::init($args);
        $args = is_array($args) ? $args : array();
        if (!isset($args['DB_TYPE'])) {
            $args = array_merge($GLOBALS['siteConfig']->getSection('database'), $args);
        }
        
        $this->connection = new db($args);

        $this->tableMap = array(
            'user'=>'users',
            'group'=>'groups',
            'groupmembers'=>'groupmembers'
        );

        $this->fieldMap = array(
            'user_userid'=>'userID',
            'user_password'=>'password',
            'user_email'=>'email',
            'user_firstname'=>'firstname',
            'user_lastname'=>'lastname',
            'user_fullname'=>'fullname',
            'group_groupname'=>'group',
            'group_gid'=>'gid',
            'group_groupmember'=>'gid',
            'groupmember_group'=>'gid',
            'groupmember_user'=>'userID',
            'groupmember_authority'=>''
        );
        
        foreach ($args as $arg=>$value) {
            if (preg_match("/^(user|group|groupmember)_(.*?)_field$/", strtolower($arg), $bits)) {
                $key = sprintf("%s_%s", $bits[1], $bits[2]);
                if (isset($this->fieldMap[$key])) {
                    $this->fieldMap[$key] = $value;
                }
            } elseif (preg_match("/^(.*?)_table$/", strtolower($arg), $bits)) {
                $key = $bits[1];
                if (isset($this->tableMap[$key])) {
                    $this->tableMap[$key] = $value;
                }
            } else {
                switch ($arg)
                {
                    case 'USER_PASSWORD_HASH':
                        if (!in_array($value, hash_algos())) {
                            throw new Exception ("Hashing algorithm $value not available");
                        }
                        $this->hashAlgo = $value;
                        break;
                    case 'USER_PASSWORD_SALT':
                        $this->hashSalt = $value;
                        break;
                    case 'GROUP_GROUPMEMBER_PROPERTY':
                        if (in_array($value, array('group','gid'))) {
                            throw new Exception("Invalid value for GROUP_GROUPMEMBER_PROPERTY $value. Should be gid or group");
                        }
                        $this->fieldMap['group_groupmember'] = $value;
                        break;
                }
            }
        }
        
    }
    
    public function getTable($table)
    {
        return isset($this->tableMap[$table]) ? $this->tableMap[$table] : null;
    }

    public function getField($field)
    {
        return isset($this->fieldMap[$field]) ? $this->fieldMap[$field] : null;
    }
    
    public function connection()
    {
        return $this->connection;
    }
    
}

/**
 * Database User
 * @package Authentication
 */
class DatabaseUser extends User
{
}

/**
 * Database Group
 * @package Authentication
 */
class DatabaseUserGroup extends UserGroup
{
    
    public function getMembers()
    {
        $property = $this->AuthenticationAuthority->getField('group_groupmember');
        if ($this->AuthenticationAuthority->getField('group_authority')) {
            $sql = sprintf("SELECT `%s`,`%s` FROM `%s` WHERE %s=?",
                $this->AuthenticationAuthority->getField('groupmember_authority'),
                $this->AuthenticationAuthority->getField('groupmember_user'),
                $this->AuthenticationAuthority->getTable('groupmembers'),
                $this->AuthenticationAuthority->getField('groupmember_group')
            );
        } else {
            $sql = sprintf("SELECT `%s` FROM `%s` WHERE %s=?",
                $this->AuthenticationAuthority->getField('groupmember_user'),
                $this->AuthenticationAuthority->getTable('groupmembers'),
                $this->AuthenticationAuthority->getField('groupmember_group')
            );
        }
        
        $connection = $this->AuthenticationAuthority->connection();
        $result = $connection->query($sql, array($this->$property));
        $members = array();
        while ($row = $result->fetch()) {
            $userID = $row[$this->AuthenticationAuthority->getField('userID')];
            if ($this->AuthenticationAuthority->getField('groupmember_authority')) {
                if (!$authority = AuthenticationAuthority::getAuthenticationAuthority($row[$this->AuthenticationAuthority->getField('authority')])) {
                    continue;
                }
            } else {
                $authority = $this->getAuthenticationAuthority();
            }

            if ($user = $authority->getUser($userID)) {
                $members[] = $user;
            }
        }

        return $members;
    }
    
    public function userIsMember(User $user)
    {
        $property = $this->AuthenticationAuthority->getField('group_groupmember');
        if ($this->AuthenticationAuthority->getField('groupmember_authority')) {
            $sql = sprintf("SELECT * FROM `%s` WHERE %s=? AND %s=? AND %s=?", 
                $this->AuthenticationAuthority->getTable('groupmembers'),
                $this->AuthenticationAuthority->getField('groupmember_group'),
                $this->AuthenticationAuthority->getField('groupmember_authority'),
                $this->AuthenticationAuthority->getField('groupmember_user')
            );
            $parameters = array($this->$property, $user->getAuthenticationAuthorityIndex(), $user->getUserID());
        } elseif ($user->getAuthenticationAuthorityIndex()==$this->getAuthenticationAuthorityIndex()) {
            //if we don't use authorities in this database then make sure the user is from the same authority
            $sql = sprintf("SELECT * FROM `%s` WHERE %s=? AND %s=?", 
                $this->AuthenticationAuthority->getTable('groupmembers'),
                $this->AuthenticationAuthority->getField('groupmember_group'),
                $this->AuthenticationAuthority->getField('groupmember_user')
            );
            $parameters = array($this->$property, $user->getUserID());
        } else {
            //user is from another authority
            return false;
        }

        $connection = $this->AuthenticationAuthority->connection();
        $result = $connection->query($sql, $parameters); 
        if ($row = $result->fetch()) {
            return true;
        }
        return false;
    }
}
