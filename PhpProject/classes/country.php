<?php
class Country {
    private int    $country_id;
    private string $name;
    private string $description;
    private string $flag_image;
    private string $display_image;
    private string $official_tourism_website;

    public function __construct(
        string $name,
        string $description,
        string $flag_image,
        string $display_image,
        string $official_tourism_website,
        int    $country_id = 0
    ) {
        $this->country_id               = $country_id;
        $this->name                     = $name;
        $this->description              = $description;
        $this->flag_image               = $flag_image;
        $this->display_image            = $display_image;
        $this->official_tourism_website = $official_tourism_website;
    }

    // Getters
    public function getCountryId():             int    { return $this->country_id; }
    public function getName():                  string { return $this->name; }
    public function getDescription():           string { return $this->description; }
    public function getFlagImage():             string { return $this->flag_image; }
    public function getDisplayImage():          string { return $this->display_image; }
    public function getOfficialTourismWebsite(): string { return $this->official_tourism_website; }

    // Setters
    public function setName(string $name):                   void { $this->name        = $name; }
    public function setDescription(string $description):     void { $this->description = $description; }
    public function setFlagImage(string $flag_image):        void { $this->flag_image  = $flag_image; }
    public function setDisplayImage(string $display_image):  void { $this->display_image = $display_image; }
    public function setOfficialTourismWebsite(string $url):  void { $this->official_tourism_website = $url; }

    public function toArray(): array {
        return [
            'country_id'               => $this->country_id,
            'name'                     => $this->name,
            'description'              => $this->description,
            'flag_image'               => $this->flag_image,
            'display_image'            => $this->display_image,
            'official_tourism_website' => $this->official_tourism_website,
        ];
    }

    public static function fromArray(array $data): self {
        return new self(
            $data['name'],
            $data['description'],
            $data['flag_image'],
            $data['display_image'],
            $data['official_tourism_website'],
            (int)($data['country_id'] ?? 0)
        );
    }
}
?>