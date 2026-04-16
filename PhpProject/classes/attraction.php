<?php
class Attraction {
    private int    $attraction_id;
    private int    $country_id;
    private int    $type_id;
    private string $name;
    private string $description;
    private string $cover_image;
    private string $created_at;

    public function __construct(
        int    $country_id,
        int    $type_id,
        string $name,
        string $description,
        string $cover_image,
        int    $attraction_id = 0,
        string $created_at = ''
    ) {
        $this->attraction_id = $attraction_id;
        $this->country_id    = $country_id;
        $this->type_id       = $type_id;
        $this->name          = $name;
        $this->description   = $description;
        $this->cover_image   = $cover_image;
        $this->view_count    = $view_count;
        $this->created_at    = $created_at ?: date('Y-m-d H:i:s');
    }

    // Getters
    public function getAttractionId(): int    { return $this->attraction_id; }
    public function getCountryId():    int    { return $this->country_id; }
    public function getTypeId():       int    { return $this->type_id; }
    public function getName():         string { return $this->name; }
    public function getDescription():  string { return $this->description; }
    public function getCoverImage():   string { return $this->cover_image; }
    public function getViewCount():    int    { return $this->view_count; }
    public function getCreatedAt():    string { return $this->created_at; }

    // Setters
    public function setName(string $name):               void { $this->name        = $name; }
    public function setDescription(string $description): void { $this->description = $description; }
    public function setCoverImage(string $cover_image):  void { $this->cover_image = $cover_image; }
    public function setTypeId(int $type_id):             void { $this->type_id     = $type_id; }
    public function incrementViewCount():                void { $this->view_count++; }

    public function toArray(): array {
        return [
            'attraction_id' => $this->attraction_id,
            'country_id'    => $this->country_id,
            'type_id'       => $this->type_id,
            'name'          => $this->name,
            'description'   => $this->description,
            'cover_image'   => $this->cover_image,
            'view_count'    => $this->view_count,
            'created_at'    => $this->created_at,
        ];
    }

    public static function fromArray(array $data): self {
        return new self(
            (int)$data['country_id'],
            (int)$data['type_id'],
            $data['name'],
            $data['description'],
            $data['cover_image'],
            (int)($data['attraction_id'] ?? 0),
            (int)($data['view_count']    ?? 0),
            $data['created_at']          ?? ''
        );
    }
}
?>