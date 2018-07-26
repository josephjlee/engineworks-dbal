<?php
namespace EngineWorks\DBAL\Tests\Sqlite;

use EngineWorks\DBAL\CommonTypes;
use EngineWorks\DBAL\Result;
use EngineWorks\DBAL\Tests\QueriesTestTrait;
use EngineWorks\DBAL\Tests\RecordsetTester;
use EngineWorks\DBAL\Tests\SqlQuoteTester;
use EngineWorks\DBAL\Tests\TestCaseWithSqliteDatabase;
use EngineWorks\DBAL\Tests\TransactionsPreventCommitTestTrait;
use EngineWorks\DBAL\Tests\TransactionsTester;
use EngineWorks\DBAL\Tests\TransactionsWithExceptionsTestTrait;

class SqliteConnectedTest extends TestCaseWithSqliteDatabase
{
    // composite with transactions trait
    use TransactionsPreventCommitTestTrait;
    use TransactionsWithExceptionsTestTrait;
    use QueriesTestTrait;

    public function testConnectAndDisconnect()
    {
        $this->dbal->disconnect();

        // connect, this is actually reconnect since TestCaseWithDatabase class fail if cannot connect
        $this->logger->clear();
        $this->assertTrue($this->dbal->connect());
        $expectedLogs = [
            'info: -- Connection success',
        ];
        $this->assertEquals($expectedLogs, $this->logger->allMessages());

        // disconnect
        $this->logger->clear();
        $this->dbal->disconnect();
        $this->assertFalse($this->dbal->isConnected());
        $expectedLogs = [
            'info: -- Disconnection',
        ];
        $this->assertEquals($expectedLogs, $this->logger->allMessages());
    }

    public function testQuoteMultibyte()
    {
        $text = 'á é í ó ú';
        $sql = 'SELECT ' . $this->dbal->sqlQuote($text, CommonTypes::TTEXT);
        $this->assertSame("SELECT '$text'", $sql);
        $this->assertSame($text, $this->dbal->queryOne($sql));
    }

    public function testQueryResult()
    {
        // it is known that sqlite does not have date, datetime, time or boolean
        $overrideTypes = [
            'lastview' => CommonTypes::TDATETIME,
            'isfree' => CommonTypes::TBOOL,
        ];
        $sql = 'SELECT * FROM albums WHERE (albumid = 5);';
        /* @var \EngineWorks\DBAL\Sqlite\Result $result */
        $result = $this->dbal->queryResult($sql, $overrideTypes);
        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(1, $result->resultCount());
        // get first
        $fetchedFirst = $result->fetchRow();
        $this->assertInternalType('array', $fetchedFirst);
        // move and get first again
        $this->assertTrue($result->moveFirst());
        $fetchedSecond = $result->fetchRow();
        // test they are the same
        $this->assertEquals($fetchedFirst, $fetchedSecond);

        $expectedFields = [
            ['name' => 'albumid', 'commontype' => CommonTypes::TINT, 'table' => ''],
            ['name' => 'title', 'commontype' => CommonTypes::TTEXT, 'table' => ''],
            ['name' => 'votes', 'commontype' => CommonTypes::TINT, 'table' => ''],
            ['name' => 'lastview', 'commontype' => CommonTypes::TDATETIME, 'table' => ''],
            ['name' => 'isfree', 'commontype' => CommonTypes::TBOOL, 'table' => ''],
            ['name' => 'collect', 'commontype' => CommonTypes::TNUMBER, 'table' => ''],
        ];
        $this->assertEquals($expectedFields, $result->getFields());
    }

    public function testRecordsetUsingTester()
    {
        $tester = new RecordsetTester($this, $this->dbal);
        $tester->execute();
    }

    public function testTransactionsUsingTester()
    {
        $tester = new TransactionsTester($this, $this->dbal);
        $tester->execute();
    }

    public function testSqlQuoteUsingTester()
    {
        $tester = new SqlQuoteTester($this, $this->dbal);
        $tester->execute();
    }
}
