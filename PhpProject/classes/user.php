<?php
class User {
    private int    $user_id;
    private string $username;
    private string $email;
    private string $hashed_password;
    private string $role;
    private string $created_at;

    public function __construct(
        string $username,
        string $email,
        string $hashed_password,
        string $role = 'visitor',
        int    $user_id = 0,
        string $created_at = ''
    ) {
        $this->user_id         = $user_id;
        $this->username        = $username;
        $this->email           = $email;
        $this->hashed_password = $hashed_password;
        $this->role            = $role;
        $this->created_at      = $created_at ?: date('Y-m-d H:i:s');
    }

    // Getters
    public function getUserId():       int    { return $this->user_id; }
    public function getUsername():     string { return $this->username; }
    public function getEmail():        string { return $this->email; }
    public function getHashedPassword(): string { return $this->hashed_password; }
    public function getRole():         string { return $this->role; }
    public function getCreatedAt():    string { return $this->created_at; }

    // Setters
    public function setUsername(string $username): void { $this->username = $username; }
    public function setEmail(string $email):       void { $this->email    = $email; }
    public function setRole(string $role):         void { $this->role     = $role; }
    public function setHashedPassword(string $hashed_password): void {
        $this->hashed_password = $hashed_password;
    }

    // Helpers
    public function isAdmin():   bool { return $this->role === 'admin'; }
    public function isCreator(): bool { return $this->role === 'creator'; }
    public function isVisitor(): bool { return $this->role === 'visitor'; }

    public function toArray(): array {
        return [
            'user_id'         => $this->user_id,
            'username'        => $this->username,
            'email'           => $this->email,
            'hashed_password' => $this->hashed_password,
            'role'            => $this->role,
            'created_at'      => $this->created_at,
        ];
    }

    public static function fromArray(array $data): self {
        return new self(
            $data['username'],
            $data['email'],
            $data['hashed_password'],
            $data['role']       ?? 'visitor',
            (int)($data['user_id']    ?? 0),
            $data['created_at'] ?? ''
        );
    }
}
?>