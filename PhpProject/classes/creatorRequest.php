<?php
class CreatorRequest {
    private int    $request_id;
    private int    $user_id;
    private string $reason;
    private string $status;
    private string $requested_at;
    private ?string $reviewed_at;

    public function __construct(
        int     $user_id,
        string  $reason,
        string  $status = 'pending',
        int     $request_id = 0,
        string  $requested_at = '',
        ?string $reviewed_at = null
    ) {
        $this->request_id   = $request_id;
        $this->user_id      = $user_id;
        $this->reason       = $reason;
        $this->status       = $status;
        $this->requested_at = $requested_at ?: date('Y-m-d H:i:s');
        $this->reviewed_at  = $reviewed_at;
    }

    // Getters
    public function getRequestId():   int     { return $this->request_id; }
    public function getUserId():      int     { return $this->user_id; }
    public function getReason():      string  { return $this->reason; }
    public function getStatus():      string  { return $this->status; }
    public function getRequestedAt(): string  { return $this->requested_at; }
    public function getReviewedAt():  ?string { return $this->reviewed_at; }

    // Setters
    public function setStatus(string $status): void {
        $this->status      = $status;
        $this->reviewed_at = date('Y-m-d H:i:s');
    }

    // Helpers
    public function isPending():  bool { return $this->status === 'pending'; }
    public function isAccepted(): bool { return $this->status === 'accepted'; }
    public function isDeclined(): bool { return $this->status === 'declined'; }
    public function accept():     void { $this->setStatus('accepted'); }
    public function decline():    void { $this->setStatus('declined'); }

    public function toArray(): array {
        return [
            'request_id'   => $this->request_id,
            'user_id'      => $this->user_id,
            'reason'       => $this->reason,
            'status'       => $this->status,
            'requested_at' => $this->requested_at,
            'reviewed_at'  => $this->reviewed_at,
        ];
    }

    public static function fromArray(array $data): self {
        return new self(
            (int)$data['user_id'],
            $data['reason'],
            $data['status']       ?? 'pending',
            (int)($data['request_id']  ?? 0),
            $data['requested_at'] ?? '',
            $data['reviewed_at']  ?? null
        );
    }
}
?>