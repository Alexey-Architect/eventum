<?php

/**
 * Test DB layer to work as expected
 */
class DbTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var DbInterface
     */
    private $db;

    public function setUp()
    {
        if (getenv('TRAVIS')) {
            $this->markTestSkipped("No DB tests in Travis");
        }

        $this->db = DB_Helper::getInstance(false);
    }

    /**
     * @dataProvider quoteData
     */
    public function testQuote($input, $exp)
    {
        $res = $this->db->escapeSimple($input);
        $this->assertEquals($exp, $res);
    }

    public function quoteData() {
        return array(
            array("C'est La Vie", "C\\'est La Vie"),
            array(array("Jää-äär"), null),
        );
    }

    public function testGetAllDefault()
    {
        $res = $this->db->getAll(
            'SELECT usr_id,usr_full_name,usr_email,usr_lang FROM {{%user}} WHERE usr_id<=?', array(2),
            DbInterface::DB_FETCHMODE_DEFAULT
        );
        $this->assertInternalType('array', $res);
        $exp = array(
            0 => array(
                0 => 1,
                1 => 'system',
                2 => 'system-account@example.com',
                3 => null,
            ),
            1 => array(
                0 => 2,
                1 => 'Admin User',
                2 => 'admin@example.com',
                3 => null,
            ),
        );
        $this->assertEquals($exp, $res);
    }

    public function testGetAllAssoc()
    {
        $res = $this->db->getAll(
            'SELECT usr_id,usr_full_name,usr_email,usr_lang FROM {{%user}} WHERE usr_id<=?', array(2),
            DbInterface::DB_FETCHMODE_ASSOC
        );
        $this->assertInternalType('array', $res);
        $exp = array(
            0 => array(
                'usr_id'        => 1,
                'usr_full_name' => 'system',
                'usr_email'     => 'system-account@example.com',
                'usr_lang'      => '',
            ),
            1 => array(
                'usr_id'        => 2,
                'usr_full_name' => 'Admin User',
                'usr_email'     => 'admin@example.com',
                'usr_lang'      => null,
            ),
        );
        $this->assertEquals($exp, $res);
    }

    public function testGetAssocTrueDefault()
    {
        $this->markTestSkipped("this combination is never used in eventum code");
        $res = $this->db->getAssoc(
            'SELECT usr_id,usr_full_name,usr_email,usr_lang FROM {{%user}} WHERE usr_id<=?',
            true, array(2),
            DbInterface::DB_FETCHMODE_DEFAULT
        );

        $this->assertInternalType('array', $res);
        $exp = array(
            1 => array(
                0 => 'system',
                1 => 'system-account@example.com',
                2 => null,
            ),
            2 => array(
                0 => 'Admin User',
                1 => 'admin@example.com',
                2 => 'en_US',
            ),
        );
        $this->assertEquals($exp, $res);
    }

    public function testGetAssocFalseDefault()
    {
        $this->markTestSkipped("this fails under yii as it tries to switch to fetchPair");

        $res = $this->db->getAssoc(
            'SELECT usr_id,usr_full_name,usr_email,usr_lang FROM {{%user}} WHERE usr_id<=?',
            false, array(2),
            DbInterface::DB_FETCHMODE_DEFAULT
        );

        $this->assertInternalType('array', $res);
        $exp = array(
            1 => array(
                0 => 'system',
                1 => 'system-account@example.com',
                2 => null,
            ),
            2 => array(
                0 => 'Admin User',
                1 => 'admin@example.com',
                2 => 'en_US',
            ),
        );
        $this->assertEquals($exp, $res);
    }

    public function testGetAssocFalseAssoc()
    {
        $res = $this->db->getAssoc(
            'SELECT usr_id,usr_full_name,usr_email,usr_lang FROM {{%user}} WHERE usr_id<=?',
            false, array(2),
            DbInterface::DB_FETCHMODE_ASSOC
        );

        $this->assertInternalType('array', $res);
        $exp = array(
            1 => array(
                'usr_full_name' => 'system',
                'usr_email'     => 'system-account@example.com',
                'usr_lang'      => null,
            ),
            2 => array(
                'usr_full_name' => 'Admin User',
                'usr_email'     => 'admin@example.com',
                'usr_lang'      => null,
            ),
        );
        $this->assertEquals($exp, $res);
    }

    public function testGetAssocTrueAssoc()
    {
        $res = $this->db->getAssoc(
            'SELECT usr_id,usr_full_name,usr_email,usr_lang FROM {{%user}} WHERE usr_id<=?',
            true, array(2),
            DbInterface::DB_FETCHMODE_ASSOC
        );

        $this->assertInternalType('array', $res);
        $exp = array(
            1 => array(
                'usr_full_name' => 'system',
                'usr_email'     => 'system-account@example.com',
                'usr_lang'      => null,
            ),
            2 => array(
                'usr_full_name' => 'Admin User',
                'usr_email'     => 'admin@example.com',
                'usr_lang'      => null,
            ),
        );
        $this->assertEquals($exp, $res);
    }

    public function testGetColumn()
    {
        $res = $this->db->getColumn(
            'SELECT usr_full_name FROM {{%user}} WHERE usr_id<=?',
            array(2)
        );

        $this->assertInternalType('array', $res);
        $exp = array(
            0 => 'system',
            1 => 'Admin User',
        );
        $this->assertEquals($exp, $res);
    }

    public function testGetOne()
    {
        $res = $this->db->getOne(
            'SELECT usr_id FROM {{%user}} WHERE usr_email=?', array('nosuchemail@.-')
        );
        $this->assertNull($res);

        $res = $this->db->getOne(
            'SELECT usr_id FROM {{%user}} WHERE usr_email=?', array('admin@example.com')
        );
        $this->assertEquals(2, $res);
    }

    public function testGetPair()
    {
        $res = $this->db->getPair(
            'SELECT usr_id,usr_full_name FROM {{%user}} WHERE usr_email=?', array('nosuchemail@.-')
        );
        $this->assertInternalType('array', $res);
        $this->assertEmpty($res);

        $res = $this->db->getPair(
            'SELECT usr_id,usr_full_name FROM {{%user}} WHERE usr_id<=2'
        );
        $this->assertInternalType('array', $res);
        $exp = array(1 => 'system', 2 => 'Admin User');
        $this->assertEquals($exp, $res);
    }

    public function testGetRowDefault()
    {
        $res = $this->db->getRow(
            'SELECT usr_id,usr_full_name,usr_email,usr_lang FROM {{%user}} WHERE usr_id<=?',
            array(2), DbInterface::DB_FETCHMODE_DEFAULT
        );

        $this->assertInternalType('array', $res);
        $exp = array(
            0 => '1',
            1 => 'system',
            2 => 'system-account@example.com',
            3 => null,
        );
        $this->assertEquals($exp, $res);
    }

    public function testGetRowAssoc()
    {
        $res = $this->db->getRow(
            'SELECT usr_id,usr_full_name,usr_email,usr_lang FROM {{%user}} WHERE usr_id<=?',
            array(2), DbInterface::DB_FETCHMODE_ASSOC
        );

        $this->assertInternalType('array', $res);
        $exp = array(
            'usr_id'        => '1',
            'usr_full_name' => 'system',
            'usr_email'     => 'system-account@example.com',
            'usr_lang'      => null,
        );
        $this->assertEquals($exp, $res);
    }
}
