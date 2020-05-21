<?php

namespace B3none\League\Helpers;

class PlayerHelper extends BaseHelper
{
    /**
     * Get player
     *
     * @param string $discordId
     * @return array
     */
    public function getPlayerByDiscordId(string $discordId): array
    {
        $query = $this->db->query('
            SELECT *, rankme.steam
            FROM players
            LEFT JOIN rankme ON rankme.steam = players.steam
            WHERE players.discord = :discord
        ', [
            ':discord' => $discordId,
        ]);

        $response = $query->fetch();

        foreach ($response as $key => $value) {
            if (is_numeric($key)) {
                unset($response[$key]);
            }
        }

        return $response ?: [
            'error' => 'not_found'
        ];
    }

    /**
     * Get player match by discord id
     *
     * @param int $discordId
     * @return array
     */
    public function getPlayerMatchByDiscordId(string $discordId): array
    {
        $query = $this->db->query('
            SELECT matches_maps.*
            FROM players
            JOIN matches_players ON matches_players.steam = players.steam 
            JOIN matches ON matches.matchid = matches_players.matchid
            JOIN matches_maps ON matches_maps.matchid = matches.matchid
            WHERE players.discord = :discord
            AND matches_maps.end_time IS NULL
        ', [
            ':discord' => $discordId,
        ]);

        $response = $query->fetch();

        foreach ($response as $key => $value) {
            if (is_numeric($key)) {
                unset($response[$key]);
            }
        }

        return $response ?: [
            'error' => 'not_found'
        ];
    }

    /**
     * Ban a player by discord id
     *
     * @param int $discordId
     * @return array
     */
    public function banPlayerByDiscordId(int $discordId): array
    {
        $update = $this->db->update('players', [
            'is_banned' => true,
        ], [
            'discord' => $discordId,
        ]);

        $response = $update->execute();

        if ($response) {
            return [
                'success' => true,
            ];
        }

        return [
            'error' => 'not_found'
        ];
    }

    /**
     * Unban a player by discord id
     *
     * @param int $discordId
     * @return array
     */
    public function unbanPlayerByDiscordId(int $discordId): array
    {
        $update = $this->db->update('players', [
            'is_banned' => false,
        ], [
            'discord' => $discordId,
        ]);

        $response = $update->execute();

        if ($response) {
            return [
                'success' => true,
            ];
        }

        return [
            'error' => 'not_found'
        ];
    }

    /**
     * Get player
     *
     * @param array $discordIds
     * @return array
     */
    public function getPlayersByDiscordIds(array $discordIds): array
    {
        $whereString = '';
        $whereVariables = [];
        $count = 0;
        foreach ($discordIds as $discordId) {
            if ($count !== 0) {
                $whereString .= ' OR ';
            }

            $whereString .= 'players.discord = :discordId' . $count;
            $whereVariables['discordId' . $count] = $discordId;
            $count++;
        }

        $query = $this->db->query("
            SELECT *, rankme.steam
            FROM players
            LEFT JOIN rankme ON rankme.steam = players.steam
            WHERE 1=1 AND (
                $whereString
            )
        ", $whereVariables);

        $response = $query->fetchAll();

        foreach ($response as $playerKey => $player) {
            foreach ($player as $key => $value) {
                if (is_numeric($key)) {
                    unset($response[$playerKey][$key]);
                }
            }
        }

        return $response ?: [
            'error' => 'none_found'
        ];
    }

    /**
     * Check whether a steamId is linked
     *
     * @param string $steamId
     * @return bool
     */
    public function addPlayer(string $steamId): bool
    {
        $query = $this->db->insert('players', [
            'steam' => $steamId,
        ]);

        return !!$query;
    }

    /**
     * Check whether a steamId is linked
     *
     * @param string $steamId
     * @return bool
     */
    public function isLinked(string $steamId): bool
    {
        $query = $this->db->query('
            SELECT * 
            FROM players
            WHERE steam = :steam
        ', [
            ':steam' => $steamId,
        ]);

        return $query->rowCount() !== 0;
    }

    /**
     * Check whether a steamId is in an ongoing game
     *
     * @param string $steamId
     * @return bool
     */
    public function isInMatch(string $steamId): bool
    {
        $response = $this->db->get('matches', [
            '[<]matches_players' => 'matchid'
        ], [
            'matches.matchid',
            'matches_players.steam'
        ], [
            'matches_players.steam' => $steamId,
            'matches.end_time' => null,
        ]);

        return !!$response;
    }
}
