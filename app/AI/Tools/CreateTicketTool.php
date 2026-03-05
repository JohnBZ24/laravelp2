<?php

namespace App\AI\Tools;

use App\AI\Contracts\Tool;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Str;

class CreateTicketTool implements Tool
{
    public function name(): string
    {
        return 'create_ticket';
    }

    public function description(): string
    {
        return 'Creates a support ticket with a non-destructive action and audit log.';
    }

    public function run(User $user, array $payload): array
    {
        if (! $user->hasPermission('tickets.create')) {
            throw new AuthorizationException('Not allowed to create tickets.');
        }

        $subject = (string) ($payload['subject'] ?? 'Support Request');
        $description = (string) ($payload['description'] ?? '');
        $ticketNumber = 'TCK-'.Str::upper(Str::random(8));

        AuditLog::query()->create([
            'user_id' => $user->id,
            'event' => 'ticket.created',
            'metadata' => [
                'ticket_number' => $ticketNumber,
                'subject' => $subject,
                'description' => $description,
            ],
        ]);

        return [
            'ticket_number' => $ticketNumber,
            'status' => 'open',
            'subject' => $subject,
        ];
    }
}
