<?php

class Media {
    private int    $media_id;
    private int    $owner_id;    // post_id OR attraction_id depending on $owner_type
    private string $owner_type;  // 'post' or 'attraction'
    private string $file_path;
    private string $media_type;

    public function __construct(
        int    $owner_id,
        string $owner_type,
        string $file_path,
        string $media_type,
        int    $media_id = 0
    ) {
        $this->media_id   = $media_id;
        $this->owner_id   = $owner_id;
        $this->owner_type = $owner_type;
        $this->file_path  = $file_path;
        $this->media_type = $media_type;
    }

    // Getters
    public function getMediaId():   int    { return $this->media_id; }
    public function getOwnerId():   int    { return $this->owner_id; }
    public function getOwnerType(): string { return $this->owner_type; }
    public function getFilePath():  string { return $this->file_path; }
    public function getMediaType(): string { return $this->media_type; }

    // Setters
    public function setFilePath(string $file_path):   void { $this->file_path  = $file_path; }
    public function setMediaType(string $media_type): void { $this->media_type = $media_type; }

    // Helpers
    public function isImage(): bool { return $this->media_type === 'image'; }
    public function isVideo(): bool { return $this->media_type === 'video'; }
    public function isForPost():       bool { return $this->owner_type === 'post'; }
    public function isForAttraction(): bool { return $this->owner_type === 'attraction'; }

    public function toArray(): array {
        return [
            'media_id'   => $this->media_id,
            'owner_id'   => $this->owner_id,
            'owner_type' => $this->owner_type,
            'file_path'  => $this->file_path,
            'media_type' => $this->media_type,
        ];
    }

    public static function fromArray(array $data): self {
        return new self(
            (int)$data['owner_id'],
            $data['owner_type'],
            $data['file_path'],
            $data['media_type'],
            (int)($data['media_id'] ?? 0)
        );
    }
} 
?>