<?php

namespace App\Livewire\Player;

use App\Services\TeamChatService;
use Illuminate\Support\Facades\File;
use Livewire\Component;

class TeamChat extends Component
{
    public string $message = '';

    public function send(TeamChatService $chat): void
    {
        $chat->send($this->message);
        $this->reset('message');
        $this->resetValidation('message');
    }

    public function render()
    {
        $chat = app(TeamChatService::class);
        $participant = $chat->participant();

        return view('livewire.player.team-chat', [
            'participant' => $participant,
            'messages' => $participant ? $chat->messages() : collect(),
            'cooldownUntil' => $participant ? $chat->cooldownUntil($participant) : 0,
            'emojis' => File::json(resource_path('data/chat-emojis.json'), JSON_THROW_ON_ERROR),
        ]);
    }
}
