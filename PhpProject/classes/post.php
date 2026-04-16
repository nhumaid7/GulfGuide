<?php
class Post {
    private int     $post_id;
    private int     $user_id;
    private int     $country_id;
    private ?int    $attraction_id;
    private string  $title;
    private string  $content;
    private string  $thumbnail;
    private string  $status;

    private string  $created_at;

    public function __construct(
        int     $user_id,
        int     $country_id,
        string  $title,
        string  $content,
        string  $thumbnail,
        string  $status = 'draft',
        ?int    $attraction_id = null,
        int     $post_id = 0,

        string  $created_at = ''
    ) {
        $this->post_id       = $post_id;
        $this->user_id       = $user_id;
        $this->country_id    = $country_id;
        $this->attraction_id = $attraction_id;
        $this->title         = $title;
        $this->content       = $content;
        $this->thumbnail     = $thumbnail;
        $this->status        = $status;
        $this->created_at    = $created_at ?: date('Y-m-d H:i:s');
    }

    // Getters
    public function getPostId():       int     { return $this->post_id; }
    public function getUserId():       int     { return $this->user_id; }
    public function getCountryId():    int     { return $this->country_id; }
    public function getAttractionId(): ?int    { return $this->attraction_id; }
    public function getTitle():        string  { return $this->title; }
    public function getContent():      string  { return $this->content; }
    public function getThumbnail():    string  { return $this->thumbnail; }
    public function getStatus():       string  { return $this->status; }
    public function getCreatedAt():    string  { return $this->created_at; }

    // Setters
    public function setTitle(string $title):         void { $this->title     = $title; }
    public function setContent(string $content):     void { $this->content   = $content; }
    public function setThumbnail(string $thumbnail): void { $this->thumbnail = $thumbnail; }
    public function setStatus(string $status):       void { $this->status    = $status; }
    public function setAttractionId(?int $id):       void { $this->attraction_id = $id; }


    // Helpers
    public function isPublished(): bool { return $this->status === 'published'; }
    public function isDraft():     bool { return $this->status === 'draft'; }
    public function publish():     void { $this->status = 'published'; }

    public function toArray(): array {
        return [
            'post_id'       => $this->post_id,
            'user_id'       => $this->user_id,
            'country_id'    => $this->country_id,
            'attraction_id' => $this->attraction_id,
            'title'         => $this->title,
            'content'       => $this->content,
            'thumbnail'     => $this->thumbnail,
            'status'        => $this->status,
          
            'created_at'    => $this->created_at,
        ];
    }

    public static function fromArray(array $data): self {
        return new self(
            (int)$data['user_id'],
            (int)$data['country_id'],
            $data['title'],
            $data['content'],
            $data['thumbnail'],
            $data['status']        ?? 'draft',
            isset($data['attraction_id']) ? (int)$data['attraction_id'] : null,
            (int)($data['post_id']    ?? 0),
            $data['created_at']    ?? ''
        );
    }
}
?>