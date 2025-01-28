<?php
require_once 'config/database.php';

class User {
    private $conn;
    private $table_name = 'users';

    // User Properties (update existing properties)
    public $id;
    public $name;
    public $email;
    public $phone;
    public $birthdate;
    public $age;
    public $gender;
    public $password;
    public $interests;
    public $favorite_color;
    public $profile_photos; // Changed from profile_picture to profile_photos
    public $deleted_profile_pictures; // For tracking deleted photos
    public $about_me; // Added about_me field
    public $blood_group;
    public $address;
    public $skills;


    public function __construct($db) {
        $this->conn = $db;
    }

    // Validation Methods
    public function validateName($name) {
        return preg_match("/^[a-zA-Z ]{3,50}$/", $name);
    }

    public function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public function validatePhone($phone) {
        return preg_match("/^[0-9]{10}$/", $phone);
    }

    public function validateAge($age) {
        return $age >= 18 && $age <= 100;
    }

    public function validatePassword($password) {
        return preg_match("/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/", $password);
    }

    public function validateBloodGroup($blood_group) {
        $valid_groups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
        return in_array($blood_group, $valid_groups);
    }

    public function validateAddress($address) {
        return strlen(trim($address)) >= 10 && strlen(trim($address)) <= 255;
    }

     public function validateAboutMe($about_me) {
        return !empty(trim(strip_tags($about_me))); // Basic validation to ensure not empty after stripping tags
    }

    public function validateSkills($skills) {
        $valid_skills = [
            'Programming', 'Design', 'Writing', 'Marketing', 
            'Data Analysis', 'Management', 'Sales', 'Customer Service'
        ];
        
        if (!is_array($skills) || empty($skills)) {
            return false;
        }

        foreach ($skills as $skill) {
            if (!in_array($skill, $valid_skills)) {
                return false;
            }
        }

        return true;
    }

    // CRUD Operations for PostgreSQL
    public function create() {
        $interests_string = is_array($this->interests) ? implode(',', $this->interests) : '';
        $skills_string = is_array($this->skills) ? implode(',', $this->skills) : '';
        
        // Convert profile_photos array to PostgreSQL array format
        $profile_photos_array = is_array($this->profile_photos) ? 
            '{' . implode(',', $this->profile_photos) . '}' : null;

        $query = "INSERT INTO {$this->table_name} (
            name, email, phone, birthdate, age, gender, password, 
            interests, favorite_color, profile_photos, about_me,
            blood_group, address, skills, deleted_profile_pictures
        ) VALUES (
            $1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15
        ) RETURNING id";
        
        $hashed_password = password_hash($this->password, PASSWORD_DEFAULT);
        
        // Initialize empty array for deleted_profile_pictures
        $deleted_photos_array = '{}';

        $params = [
            $this->name, 
            $this->email, 
            $this->phone, 
            $this->birthdate,
            $this->age, 
            $this->gender, 
            $hashed_password,
            $interests_string,
            $this->favorite_color,
            $profile_photos_array,
            $this->about_me,
            $this->blood_group,
            $this->address,
            $skills_string,
            $deleted_photos_array
        ];

        try {
            $result = pg_query_params($this->conn, $query, $params);
            
            if ($result) {
                $row = pg_fetch_row($result);
                $this->id = $row[0];
                return true;
            }
            return false;
        } catch (Exception $e) {
            error_log("User creation error: " . $e->getMessage());
            return false;
        }
    }

    public function read() {
        try {
            $query = "SELECT id, name, email, phone, birthdate, age, gender, 
                      interests, favorite_color, profile_photos, about_me,
                      CASE WHEN is_active = true THEN 't' ELSE 'f' END AS is_active,
                      blood_group, address, skills, deleted_profile_pictures
                      FROM {$this->table_name}";
            return pg_query($this->conn, $query);
        } catch (Exception $e) {
            error_log("Error reading users: " . $e->getMessage());
            return false;
        }
    }

    public function readById($id) {
        $query = "SELECT 
            id, name, email, phone, birthdate, age, gender, 
            string_to_array(interests, ',') as interests, 
            favorite_color,
            profile_photos,
            about_me,
            blood_group, address, 
            string_to_array(skills, ',') as skills,
            deleted_profile_pictures
        FROM {$this->table_name} 
        WHERE id = $1";
        
        $result = pg_query_params($this->conn, $query, [$id]);
        $row = pg_fetch_assoc($result);
        
        if ($row) {
            // Convert PostgreSQL arrays to PHP arrays
            $row['profile_photos'] = $row['profile_photos'] ? array_filter(explode(',', trim($row['profile_photos'], '{}'))) : [];
            $row['deleted_profile_pictures'] = $row['deleted_profile_pictures'] ? array_filter(explode(',', trim($row['deleted_profile_pictures'], '{}'))) : [];
        }
        
        return $row;
    }

    // Update method to include new fields
    public function update() {
        $interests_string = is_array($this->interests) ? implode(',', $this->interests) : '';
        $skills_string = is_array($this->skills) ? implode(',', $this->skills) : '';
        
        // Convert profile_photos array to PostgreSQL array format
        $profile_photos_array = is_array($this->profile_photos) ? 
            '{' . implode(',', $this->profile_photos) . '}' : '{}';
            
        // Handle deleted_profile_pictures
        $deleted_photos_array = is_string($this->deleted_profile_pictures) ? 
            $this->deleted_profile_pictures : '{}';
    
        $query = "UPDATE {$this->table_name} 
                  SET name=$1, email=$2, phone=$3, 
                      birthdate=$4, age=$5, gender=$6, 
                      interests=$7, favorite_color=$8, 
                      profile_photos=$9, about_me=$10,
                      blood_group=$11, address=$12, skills=$13,
                      deleted_profile_pictures=$14
                  WHERE id=$15
                  RETURNING id";
        
        $params = [
            $this->name, 
            $this->email, 
            $this->phone, 
            $this->birthdate,
            $this->age, 
            $this->gender, 
            $interests_string,
            $this->favorite_color,
            $profile_photos_array,
            $this->about_me,
            $this->blood_group,
            $this->address,
            $skills_string,
            $deleted_photos_array,
            $this->id
        ];
    
        try {
            $result = pg_query_params($this->conn, $query, $params);
            if ($result) {
                $row = pg_fetch_row($result);
                return true;
            }
            return false;
        } catch (Exception $e) {
            error_log("User update error: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateStatus($id, $status) {

        $query = "UPDATE {$this->table_name} SET is_active = $2, updated_at = CURRENT_TIMESTAMP WHERE id = $1";
    
        try {
            $result = pg_query_params($this->conn, $query, [$id, $status ? 't' : 'f']);
            return $result ? true : false;
        } catch (Exception $e) {
            error_log("User status update error: " . $e->getMessage());
            return false;
        }
    }

    public function delete($id) {

        $query = "UPDATE {$this->table_name} SET is_active = 'f', updated_at = CURRENT_TIMESTAMP WHERE id = $1";
    
        try {
            $result = pg_query_params($this->conn, $query, [$id]);
            return $result ? true : false;
        } catch (Exception $e) {
            error_log("User status update error: " . $e->getMessage());
            return false;
        }
    }
    

    // Additional helper methods
    public function calculateAge($birthdate) {
        $birth = new DateTime($birthdate);
        $today = new DateTime('today');
        return $birth->diff($today)->y;
    }

    public function checkEmailExists($email) {
        $query = "SELECT COUNT(*) FROM {$this->table_name} WHERE email = $1";
        $result = pg_query_params($this->conn, $query, [$email]);
        $row = pg_fetch_row($result);
        return $row[0] > 0;
    }

    // Authentication method
    public function authenticate($email, $password) {
        $query = "SELECT id, password, role, is_active FROM {$this->table_name} WHERE email = $1";
        $result = pg_query_params($this->conn, $query, [$email]);
        $user = pg_fetch_assoc($result);
    
        if ($user && password_verify($password, $user['password'])) {
            return [
                'success' => true,
                'role' => $user['role'],
                'id' => $user['id'],
                'is_active' => $user['is_active'],
            ];
        }
        return ['success' => false];
    }

    public function search($searchTerm): mixed {
        try {
            $searchTerm = '%' . $searchTerm . '%';
            $query = "SELECT id, name, email, phone, birthdate, age, gender, 
                      interests, favorite_color, profile_photos, about_me,
                      CASE WHEN is_active = true THEN 't' ELSE 'f' END AS is_active,
                      blood_group, address, skills, deleted_profile_pictures
                      FROM {$this->table_name}
                      WHERE name ILIKE $1 
                      OR email ILIKE $1 
                      OR phone ILIKE $1 
                      OR blood_group ILIKE $1 
                      OR gender ILIKE $1
                      OR interests ILIKE $1
                      OR skills ILIKE $1";
            
            return pg_query_params($this->conn, $query, [$searchTerm]);
        } catch (Exception $e) {
            error_log("Search error: " . $e->getMessage());
            return false;
        }
    }
}
?>