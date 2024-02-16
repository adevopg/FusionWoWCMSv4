<?php

use MX\CI;

defined('BASEPATH') or die('Silence is golden.');

/**
 * @package FusionCMS
 * @version 8.x
 */

/**
 * Abstraction layer for supporting different emulators
 */
class Trinity_df implements Emulator
{
    protected $config;

    /**
     * Whether or not this emulator supports remote console
     */
    protected $hasConsole = true;

    /**
     * Whether or not this emulator supports character stats
     */
    protected $hasStats = true;

    /**
     * Console object
     */
    protected $console;

    /**
     * Array of table names
     */
    protected $tables = array(
        'account'                  => 'account',
        'account_access'           => 'account_access',
        'account_banned'           => 'account_banned',
        'ip_banned'                => 'ip_banned',
        'battlenet_accounts'       => 'battlenet_accounts',
        'characters'               => 'characters',
        'item_template'            => 'item_template',
        'item_instance_transmog'   => 'item_instance_transmog',
        'character_stats'          => 'character_stats',
        'guild_member'             => 'guild_member',
        'guild'                    => 'guild',
        'gm_tickets'               => 'gm_bug'
    );

    /**
     * Array of column names
     */
    protected $columns = array(

        'account' => array(
            'id'          => 'id',
            'username'    => 'username',
            'salt'        => 'salt',
            'verifier'    => 'verifier',
            'email'       => 'email',
            'joindate'    => 'joindate',
            'last_ip'     => 'last_ip',
            'last_login'  => 'last_login',
            'expansion'   => 'expansion',
        ),

        'account_access' => array(
            'id'      => 'AccountId',
            'gmlevel' => 'SecurityLevel'
        ),

        'account_banned' => array(
            'id'        => 'id',
            'banreason' => 'banreason',
            'active'    => 'active',
            'bandate'   => 'bandate',
            'unbandate' => 'unbandate',
            'bannedby'  => 'bannedby'
        ),

        'battlenet_accounts' => array(
            'id'            => 'id',
            'email'         => 'email',
            'salt'          => 'salt',
            'verifier'      => 'verifier',
            'sha_pass_hash' => 'sha_pass_hash',
            'joindate'      => 'joindate',
            'last_ip'       => 'last_ip',
            'last_login'    => 'last_login'
        ),

        'ip_banned' => array(
            'ip'        => 'ip',
            'bandate'   => 'bandate',
            'unbandate' => 'unbandate',
            'bannedby'  => 'bannedby',
            'banreason' => 'banreason',
        ),

        'characters' => array(
            'guid'             => 'guid',
            'account'          => 'account',
            'name'             => 'name',
            'race'             => 'race',
            'class'            => 'class',
            'gender'           => 'gender',
            'level'            => 'level',
            'zone'             => 'zone',
            'online'           => 'online',
            'money'            => 'money',
            'totalKills'       => 'totalKills',
            'todayKills'       => 'todayKills',
            'yesterdayKills'   => 'yesterdayKills',
            'arenaPoints'      => 'arenaPoints',
            'totalHonorPoints' => 'totalHonorPoints',
            'position_x'       => 'position_x',
            'position_y'       => 'position_y',
            'position_z'       => 'position_z',
            'orientation'      => 'orientation',
            'map'              => 'map'
        ),

        'item_template' => array(
            'entry'                   => 'entry',
            'name'                    => 'name',
            'Quality'                 => 'Quality',
            'InventoryType'           => 'InventoryType',
            'RequiredLevel'           => 'RequiredLevel',
            'ItemLevel'               => 'ItemLevel',
            'class'                   => 'class',
            'subclass'                => 'subclass'
        ),

        'character_stats' => array(
            'guid'          => 'guid',
            'maxhealth'     => 'maxhealth',
            'maxpower1'     => 'maxpower1',
            'maxpower2'     => 'maxpower2',
            'maxpower3'     => 'maxpower3',
            'maxpower4'     => 'maxpower4',
            'maxpower5'     => 'maxpower5',
            'maxpower6'     => 'maxpower6',
            'maxpower7'     => 'maxpower7',
            'maxpower8'     => 'maxpower8',
            'maxpower9'     => 'maxpower9',
            'maxpower10'    => 'maxpower10',
            'strength'      => 'strength',
            'agility'       => 'agility',
            'stamina'       => 'stamina',
            'intellect'     => 'intellect',
            'armor'         => 'armor',
            'blockPct'      => 'blockPct',
            'dodgePct'      => 'dodgePct',
            'parryPct'      => 'parryPct',
            'critPct'       => 'critPct',
            'rangedCritPct' => 'rangedCritPct',
            'spellCritPct'  => 'spellCritPct',
            'attackPower'   => 'attackPower',
            'spellPower'    => 'spellPower',
            'resilience'    => 'resilience',
            'mastery'       => 'mastery',
            'versatility'   => 'versatility'
        ),

        'item_instance_transmog' => array(
            'itemGuid'       => 'itemGuid',
            'transmogrifyId' => 'itemModifiedAppearanceAllSpecs'
        ),

        'guild' => array(
            'guildid'    => 'guildid',
            'name'       => 'name',
            'leaderguid' => 'leaderguid'
        ),

        'guild_member' => array(
            'guildid' => 'guildid',
            'guid'    => 'guid'
        ),

        'gm_tickets' => array(
            'ticketId'   => 'Id',
            'guid'       => 'playerGuid',
            'message'    => 'note',
            'createTime' => 'createTime',
            'completed'  => 'comment',
            'closedBy'   => 'closedBy'
        )
    );

    /**
     * Array of queries
     */
    protected $queries = array(
        'get_ip_banned'             => 'SELECT ip, bandate, bannedby, banreason, unbandate FROM ip_banned WHERE ip=? AND unbandate > ?',
        'get_character'             => 'SELECT * FROM characters WHERE guid=?',
        'get_rank'                  => 'SELECT AccountId id, SecurityLevel gmlevel, RealmID RealmID FROM account_access WHERE AccountId=?',
        'get_banned'                => 'SELECT id id, bandate bandate, bannedby bannedby, banreason banreason, active active FROM account_banned WHERE id=? AND active=1',
        'get_charactername_by_guid' => 'SELECT name name FROM characters WHERE guid = ?',
        'find_guilds'               => 'SELECT g.guildid guildid, g.name name, COUNT(g_m.guid) GuildMemberCount, g.leaderguid leaderguid, c.name leaderName FROM guild g, guild_member g_m, characters c WHERE g.leaderguid = c.guid AND g_m.guildid = g.guildid AND g.name LIKE ? GROUP BY g.guildid',
        'get_inventory_item'        => 'SELECT slot slot, item item, itemEntry itemEntry, enchantments enchantments FROM character_inventory, item_instance WHERE character_inventory.item = item_instance.guid AND character_inventory.slot >= 0 AND character_inventory.slot <= 18 AND character_inventory.guid=? AND character_inventory.bag=0',
        'get_guild_members'         => 'SELECT m.guildid guildid, m.guid guid, c.name name, c.race race, c.class class, c.gender gender, c.level level, m.rank member_rank, r.rname rname, r.rights rights FROM guild_member m JOIN guild_rank r ON m.guildid = r.guildid AND m.rank = r.rid JOIN characters c ON c.guid = m.guid WHERE m.guildid = ? ORDER BY r.rights DESC',
        'get_guild'                 => 'SELECT guildid guildid, name guildName, leaderguid leaderguid, motd motd, createdate createdate FROM guild WHERE guildid = ?'
    );

    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * Get the name of a table
     *
     * @param  String $name
     * @return String
     */
    public function getTable($name)
    {
        if (!isset($this->tables[$name])) {
            return null;
        }
        return $this->tables[$name];
    }

    /**
     * Get the name of a column
     *
     * @param  String $table
     * @param  String $name
     * @return String
     */
    public function getColumn($table, $name)
    {
        if (!isset($this->columns[$table][$name])) {
            return null;
        }
        return $this->columns[$table][$name];
    }

    /**
     * Get a set of all columns
     *
     * @param  String $name
     * @return String
     */
    public function getAllColumns($table)
    {
        if (!isset($this->columns[$table])) {
            return null;
        }
        return $this->columns[$table];
    }

    /**
     * Get a pre-defined query
     *
     * @param  String $name
     * @return String
     */
    public function getQuery($name)
    {
        if (!isset($this->queries[$name])) {
            return null;
        }
        return $this->queries[$name];
    }

    /**
     * Whether or not console actions are enabled for this emulator
     *
     * @return Boolean
     */
    public function hasConsole()
    {
        return $this->hasConsole;
    }

    /**
     * Whether or not character stats are logged in the database
     *
     * @return Boolean
     */
    public function hasStats()
    {
        return $this->hasStats;
    }

    /**
     * Send console command
     *
     * @param String $command
     */
    public function sendCommand($command, $realm = false)
    {
        $this->send($command, $realm);
    }

    /**
     * Send mail via ingame mail to a specific character
     *
     * @param String $character
     * @param String $subject
     * @param String $body
     */
    public function sendMail($character, $subject, $body)
    {
        $this->send(".send mail " . $character . " \"" . $subject . "\" \"" . $body . "\"");
    }

    /**
     * Send money via ingame mail to a specific character
     *
     * @param String $character
     * @param String $subject
     * @param String $text
     * @param String $money
     */
    public function sendMoney($character, $subject, $text, $money)
    {
        $this->send(".send money " . $character . " \"" . $subject . "\" \"" . $text . "\" " . $money);
    }

    /**
     * Send items via ingame mail to a specific character
     *
     * @param String $character
     * @param String $subject
     * @param String $body
     * @param Array $items
     */
    public function sendItems($character, $subject, $body, $items)
    {
        $item_command = array();
        $mail_id = 0;
        $item_count = 0;
        $item_stacks = array();

        foreach ($items as $i) {
            // Check if item has been added
            if (!isset($item_stacks[$i['id']])) {
                // Load the item row
                $item_row = CI::$APP->realms->getRealm($this->config['id'])->getWorld()->getItem($i['id']);

                // Add the item to the stacks array
                $item_stacks[$i['id']] = array(
                    'id'        => $i['id'],
                    'count'     => array(1),
                    'stack_id'  => 0,
                    'max_count' => $item_row['stackable'],
                );

                continue;
            }

            // If stack is full
            if ($item_stacks[$i['id']]['max_count'] == $item_stacks[$i['id']]['count'][$item_stacks[$i['id']]['stack_id']]) {
                // Create a new stack
                $item_stacks[$i['id']]['stack_id']++;
                $item_stacks[$i['id']]['count'][$item_stacks[$i['id']]['stack_id']] = 0;
            }

            // Add one to the currently active stack
            $item_stacks[$i['id']]['count'][$item_stacks[$i['id']]['stack_id']]++;
        }

        // Loop through all items
        foreach ($item_stacks as $item) {
            foreach ($item['count'] as $count) {
                // Limit to 8 items per mail
                if ($item_count > 8) {
                    // Reset item count
                    $item_count = 0;

                    // Queue a new mail
                    $mail_id++;
                }

                // Increase the item count
                $item_count++;

                if (!isset($item_command[$mail_id])) {
                    $item_command[$mail_id] = '';
                }

                // Append the command
                $item_command[$mail_id] .= ' ' . $item['id'] . ':' . $count;
            }
        }

        // Send all the queued mails
        for ($i = 0; $i <= $mail_id; $i++) {
            // .send item
            $this->send("send items " . $character . " \"" . $subject . "\" \"" . $body . "\"" . $item_command[$i]);
        }
    }

    /**
     * Send a console command
     *
     * @param  String $command
     * @return void
     */
    public function send($command, $realm = false)
    {
        $blacklistCommands = array('account set', 'server shutdown', 'server exit', 'server restart', 'disable add', 'disable remove');

        foreach ($blacklistCommands as $blacklist) {
            if (strpos($command, $blacklist))
                die("Something went wrong! There is no access to execute this command." . ($realm ? '<br/><br/><b>Realm:</b> <br />' . $realm->getName() : ''));
        }

        $client = new SoapClient(null,
            array(
                'location' => 'http://' . $this->config['hostname'] . ':' . $this->config['console_port'],
                'uri'      => 'urn:TC',
                'login'    => $this->config['console_username'],
                'password' => $this->config['console_password'],
            )
        );

        try {
            $client->executeCommand(new SoapParam($command, 'command'));
        } catch (Exception $e) {
            die("Something went wrong! An administrator has been noticed and will send your order as soon as possible.<br /><br /><b>Error:</b> <br />" . $e->getMessage() . ($realm ? '<br/><br/><b>Realm:</b> <br />' . $realm->getName() : ''));
        }
    }
}
