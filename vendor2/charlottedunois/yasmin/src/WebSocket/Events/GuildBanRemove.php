<?php
/**
 * Yasmin
 * Copyright 2017-2019 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\WebSocket\Events;

/**
 * WS Event
 * @see https://discordapp.com/developers/docs/topics/gateway#guild-ban-remove
 * @internal
 */
class GuildBanRemove implements \CharlotteDunois\Yasmin\Interfaces\WSEventInterface {
    /**
     * The client.
     * @var \CharlotteDunois\Yasmin\Client
     */
    protected $client;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client, \CharlotteDunois\Yasmin\WebSocket\WSManager $wsmanager) {
        $this->client = $client;
    }
    
    function handle(\CharlotteDunois\Yasmin\WebSocket\WSConnection $ws, $data): void {
        $guild = $this->client->guilds->get($data['guild_id']);
        if($guild) {
            $user = $this->client->users->patch($data['user']);
            if($user) {
                $user = \React\Promise\resolve($user);
            } else {
                $user = $this->client->fetchUser($data['user']['id']);
            }
        
            $user->done(function (\CharlotteDunois\Yasmin\Models\User $user) use ($guild) {
                $this->client->queuedEmit('guildBanRemove', $guild, $user);
            }, array($this->client, 'handlePromiseRejection'));
        }
    }
}
