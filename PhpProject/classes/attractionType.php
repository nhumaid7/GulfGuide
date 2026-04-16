<?php
class AttractionType {
    private int    $type_id;
    private string $name;

    public function __construct(
        string $name,
        int    $type_id = 0
    ) {
        $this->type_id = $type_id;
        $this->name    = $name;
    }

    // Getters
    public function getTypeId(): int    { return $this->type_id; }
    public function getName():   string { return $this->name; }

    // Setters
    public function setName(string $name): void { $this->name = $name; }

    public function toArray(): array {
        return [
            'type_id' => $this->type_id,
            'name'    => $this->name,
        ];
    }

    public static function fromArray(array $data): self {
        return new self(
            $data['name'],
            (int)($data['type_id'] ?? 0)
        );
    }
}
?>