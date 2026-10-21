<?php

declare(strict_types=1);

namespace App\Controllers;

use Anthropic\Core\Exceptions\APIConnectionException;
use Anthropic\Core\Exceptions\APIStatusException;
use Anthropic\Core\Exceptions\AuthenticationException;
use Anthropic\Core\Exceptions\RateLimitException;
use App\Models\AuditLog;
use App\Services\Auth;
use App\Services\Chat\ChatRules;
use App\Services\Chat\ClaudeChat;
use App\Services\Database;

/** ผู้ช่วยตอบคำถาม: ตอบเองจากคำสำคัญก่อน ถ้าไม่เข้าใจและเปิดใช้ AI จึงส่งต่อให้ Claude */
final class ChatController extends Controller
{
    private const MAX_LENGTH = 500;
    /** จำนวนข้อความล่าสุดที่ส่งให้ AI เป็นบริบท (เก็บใน session ฝั่งเซิร์ฟเวอร์ ผู้ใช้ปลอมแปลงไม่ได้) */
    private const HISTORY_SIZE = 8;

    public function ask(): void
    {
        $question = trim((string) ($this->input()['message'] ?? ''));
        if ($question === '' || mb_strlen($question) > self::MAX_LENGTH) {
            $this->json(['error' => 'กรุณาพิมพ์คำถามความยาวไม่เกิน ' . self::MAX_LENGTH . ' ตัวอักษร'], 422);
            return;
        }

        $answer = ChatRules::answer($question);
        $source = 'rules';
        if ($answer === null) {
            [$answer, $source] = $this->askAi($question);
        }

        $history = $_SESSION['chat_history'] ?? [];
        $history[] = ['role' => 'user', 'content' => $question];
        $history[] = ['role' => 'assistant', 'content' => $answer['reply']];
        $_SESSION['chat_history'] = array_slice($history, -self::HISTORY_SIZE);

        $this->json($answer + ['source' => $source]);
    }

    public function reset(): void
    {
        unset($_SESSION['chat_history']);
        $this->json(['ok' => true]);
    }

    /**
     * @return array{0: array{reply: string, links: array<int, array{label: string, url: string}>}, 1: string}
     */
    private function askAi(string $question): array
    {
        if (!ClaudeChat::enabled()) {
            return [$this->notUnderstood('ยังไม่เข้าใจคำถามนี้'), 'fallback'];
        }
        $used = (int) Database::value(
            "SELECT COUNT(*) FROM audit_logs WHERE user_id = ? AND action = 'Chat AI' AND created_at > (NOW() - INTERVAL 1 HOUR)",
            [Auth::id()]
        );
        if ($used >= (int) config('app.chat.ai_per_hour')) {
            return [$this->notUnderstood('ใช้ผู้ช่วย AI ครบโควตาชั่วโมงนี้แล้ว ระหว่างนี้ถามคำถามพื้นฐานได้'), 'fallback'];
        }

        @set_time_limit(120);
        try {
            $result = ClaudeChat::ask($question, $_SESSION['chat_history'] ?? []);
        } catch (AuthenticationException $e) {
            error_log((string) $e);
            return [$this->notUnderstood('ผู้ช่วย AI ตั้งค่า API key ไม่ถูกต้อง กรุณาแจ้งผู้ดูแลระบบ'), 'fallback'];
        } catch (RateLimitException $e) {
            return [$this->notUnderstood('ผู้ช่วย AI มีผู้ใช้งานจำนวนมาก กรุณาลองใหม่อีกครู่'), 'fallback'];
        } catch (APIConnectionException $e) {
            error_log((string) $e);
            return [$this->notUnderstood('เชื่อมต่อผู้ช่วย AI ไม่ได้ (ตรวจสอบอินเทอร์เน็ตของเซิร์ฟเวอร์)'), 'fallback'];
        } catch (APIStatusException $e) {
            error_log((string) $e);
            return [$this->notUnderstood('ผู้ช่วย AI ขัดข้องชั่วคราว'), 'fallback'];
        }

        // บันทึกเฉพาะจำนวน token ไม่บันทึกข้อความคำถาม
        AuditLog::record('Chat AI', "ถามผู้ช่วย AI (input {$result['usage']['input']} / output {$result['usage']['output']} tokens)");

        return [['reply' => $result['reply'], 'links' => []], 'ai'];
    }

    /** @return array{reply: string, links: array<int, array{label: string, url: string}>} */
    private function notUnderstood(string $reason): array
    {
        return [
            'reply' => "{$reason} ลองถามแบบนี้:\n• ห้องไหนว่างตอนนี้\n• ห้องว่างพรุ่งนี้ 13:00-15:00\n• การจองของฉัน\n• วันลาเหลือเท่าไร\n• สถานะแจ้งซ่อมของฉัน\n• วันหยุดที่จะถึง",
            'links' => [],
        ];
    }
}
