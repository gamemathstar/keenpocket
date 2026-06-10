<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp template messaging via the Meta Cloud API. Best-effort and
 * config-gated (config/whatsapp.php); a no-op returning false while disabled,
 * so reminder code can always call it and fall back to push/SMS.
 */
class WhatsAppSender
{
    public function enabled(): bool
    {
        return (bool) config('whatsapp.enabled', false);
    }

    /**
     * Send an approved template to a phone number.
     *
     * @param  string  $templateKey  logical key from config('whatsapp.templates')
     * @param  array<int,string>  $params  ordered body parameters ({{1}}, {{2}}…)
     */
    public function send(string $phone, string $templateKey, array $params = []): bool
    {
        if (!$this->enabled() || trim($phone) === '') {
            return false;
        }

        $templateName = config("whatsapp.templates.{$templateKey}", $templateKey);
        $provider = config('whatsapp.provider', 'log');

        try {
            switch ($provider) {
                case 'log':
                    Log::info("[whatsapp][log] to {$phone} template={$templateName} params=".json_encode($params));
                    return true;

                case 'meta':
                    $cfg = config('whatsapp.providers.meta');
                    $resp = Http::withToken($cfg['token'])->post(
                        rtrim($cfg['base_url'], '/').'/'.$cfg['phone_number_id'].'/messages',
                        [
                            'messaging_product' => 'whatsapp',
                            'to' => $phone,
                            'type' => 'template',
                            'template' => [
                                'name' => $templateName,
                                'language' => ['code' => $cfg['lang']],
                                'components' => [[
                                    'type' => 'body',
                                    'parameters' => array_map(fn ($p) => ['type' => 'text', 'text' => (string) $p], $params),
                                ]],
                            ],
                        ]
                    );
                    return $resp->successful();

                default:
                    Log::warning("[whatsapp] unknown provider '{$provider}'");
                    return false;
            }
        } catch (\Throwable $e) {
            Log::warning("[whatsapp] delivery failed via {$provider}: ".$e->getMessage());
            return false;
        }
    }
}
