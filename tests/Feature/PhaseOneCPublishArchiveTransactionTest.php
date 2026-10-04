<?php

namespace Tests\Feature;

use Tests\TestCase;

class PhaseOneCPublishArchiveTransactionTest extends TestCase
{
    public function test_publish_archives_before_transaction_commit(): void
    {
        $source = file_get_contents(app_path('Services/OutgoingLetterService.php'));
        self::assertIsString($source);
        $start = strpos($source, 'public function publish(');
        $end = strpos($source, 'public function send(', $start);
        self::assertNotFalse($start);
        self::assertNotFalse($end);
        $method = substr($source, $start, $end - $start);
        $archive = strpos($method, '$this->archive->archive(');
        $transactionEnd = strpos($method, '}, 3);');
        self::assertNotFalse($archive);
        self::assertNotFalse($transactionEnd);
        self::assertLessThan($transactionEnd, $archive);
        self::assertStringContainsString('catch (\\Throwable $exception)', $method);
        self::assertStringContainsString('Storage::delete($path)', $method);
    }
}
