<?php

class my5280_League_8ballsingles
{
    public function __construct($league)
    {
        $this->_league = $league;
    }

    public function display_settings()
    {
        $games_per_match = '';
        if(isset($this->_league->league_format_setting)) {
            if(isset($this->_league->league_format_setting['games_per_match'])) {
                $games_per_match = $this->_league->league_format_setting['games_per_match'];
            }
        }

        ?><tr valign="top">
            <th scope="row"><label for="league_format_setting_games_per_match">Games per Match</label></th>
            <td>
                <input type="text" maxlength="2" size="2" name="settings[league_format_setting][games_per_match]" id="league_format_setting_games_per_match" value="<?php print $games_per_match; ?>"/>
            </td>
        </tr><?php
    }

    public function get_max_players()
    {
        return 1;
    }

    protected $_league;
}
