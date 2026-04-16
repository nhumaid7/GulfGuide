<?php

class Reaction {
    private int    $reaction_id;
    private int    $post_id;
    private int    $user_id;
    private string $type;
    private string $created_at;

    public function __construct(
        int    $post_id,
        int    $user_id,
        string $type,
        int    $reaction_id = 0,
        string $created_at = ''
    ) {
        $this->reaction_id = $reaction_id;
        $this->post_id     = $post_id;
        $this->user_id     = $user_id;
        $this->type        = $type;
        $this->created_at  = $created_at ?: date('Y-m-d H:i:s');
    }

    // Getters
    public function getReactionId(): int    { return $this->reaction_id; }
    public function getPostId():     int    { return $this->post_id; }
    public function getUserId():     int    { return $this->user_id; }
    public function getType():       string { return $this->type; }
    public function getCreatedAt():  string { return $this->created_at; }

    // Setters
    public function setType(string $type): void { $this->type = $type; }

    // Helpers
    public function isLike():    bool { return $this->type === 'like'; }
    public function isDislike(): bool { return $this->type === 'dislike'; }

    public function toArray(): array {
        return [
            'reaction_id' => $this->reaction_id,
            'post_id'     => $this->post_id,
            'user_id'     => $this->user_id,
            'type'        => $this->type,
            'created_at'  => $this->created_at,
        ];
    }

    public static function fromArray(array $data): self {
        return new self(
            (int)$data['post_id'],
            (int)$data['user_id'],
            $data['type'],
            (int)($data['reaction_id'] ?? 0),
            $data['created_at']        ?? ''
        );
    }
}


?>