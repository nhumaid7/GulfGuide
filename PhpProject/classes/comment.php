<?php
class Comment {
    private int    $comment_id;
    private int    $post_id;
    private int    $user_id;
    private string $content;
    private int    $is_visible;
    private string $created_at;

    public function __construct(
        int    $post_id,
        int    $user_id,
        string $content,
        int    $is_visible = 1,
        int    $comment_id = 0,
        string $created_at = ''
    ) {
        $this->comment_id  = $comment_id;
        $this->post_id     = $post_id;
        $this->user_id     = $user_id;
        $this->content     = $content;
        $this->is_visible  = $is_visible;
        $this->created_at  = $created_at ?: date('Y-m-d H:i:s');
    }

    // Getters
    public function getCommentId(): int    { return $this->comment_id; }
    public function getPostId():    int    { return $this->post_id; }
    public function getUserId():    int    { return $this->user_id; }
    public function getContent():   string { return $this->content; }
    public function getIsVisible(): int    { return $this->is_visible; }
    public function getCreatedAt(): string { return $this->created_at; }

    // Setters
    public function setContent(string $content): void { $this->content    = $content; }
    public function hide():                      void { $this->is_visible = 0; }
    public function show():                      void { $this->is_visible = 1; }

    public function isVisible(): bool { return $this->is_visible === 1; }

    public function toArray(): array {
        return [
            'comment_id' => $this->comment_id,
            'post_id'    => $this->post_id,
            'user_id'    => $this->user_id,
            'content'    => $this->content,
            'is_visible' => $this->is_visible,
            'created_at' => $this->created_at,
        ];
    }

    public static function fromArray(array $data): self {
        return new self(
            (int)$data['post_id'],
            (int)$data['user_id'],
            $data['content'],
            (int)($data['is_visible'] ?? 1),
            (int)($data['comment_id'] ?? 0),
            $data['created_at']       ?? ''
        );
    }
}







?>