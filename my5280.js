window.my5280 = {
    'nextPlayerIndex': 0,
    'nextPaidIndex': 0,
    'nextHandicapIndex': 0,
    'nextScoreIndex': 0
};

my5280.init = function()
{
    // Hide the other player select boxes
    jQuery('.teamPlayer').each(function() {
        if(this.options[this.selectedIndex].value == 'OTHER') {
            jQuery(this).hide();
            jQuery(this.parentNode).find('.otherPlayer').show();
        } else {
            jQuery(this.parentNode).find('.otherPlayer').hide();
        }
    });

    // Copy other players from the first select box
    var allPlayers = document.getElementById('otherPlayer0');
    if(allPlayers) {
        allPlayers = allPlayers.options;
        jQuery('select.otherPlayer').each(function() {
            var sel = this.getAttribute('sel');

            for(var i = 1; i < allPlayers.length - 1; i++) {
                if(this.id != 'otherPlayer0') {
                    this.appendChild(allPlayers[i].cloneNode(true));
                }
                if(allPlayers[i].value == sel) {
                    this.selectedIndex = i;
                }
            }
        });
    }

    // Handle changes to form elements
    jQuery('.scoresheet input,select').change(my5280.handleChange);
}

my5280.handleChange = function()
{
    // Get information
    var pos = this.name.indexOf('[');
    var name = this.name.substr(0, pos);
    var value = jQuery(this).val();

    // Perform some specific processing
    switch(name) {
    case 'player':
    case 'otherPlayer':
        // Hide or show the other player select
        if(name == 'player') {
            if(this.options[this.selectedIndex].value == 'OTHER') {
                jQuery(this.parentNode).find('.otherPlayer').show();
            } else {
                jQuery(this.parentNode).find('.otherPlayer').hide();
            }
        }

        // Update the handicap
        var handicap = this.options[this.selectedIndex].getAttribute('handicap');
        if(handicap) {
            jQuery(this.parentNode.parentNode).find('.handicap input').each(function() {
                jQuery(this).val(handicap);
            });
        }

        jQuery('.scoresheet .HOMEgame').each(my5280.handleChange);
        break;
    case 'handicap':
        my5280.updateTotals();
        break;
    case 'score':
        // Determine the home game
        var homeRound = parseInt(this.parentNode.getAttribute('round'));
        var homePlayer = parseInt(this.parentNode.getAttribute('player'));
        var homeGame = /HOMEgame(\d+)/.exec(this.className);
        homeGame = parseInt(homeGame[1]);

        // Determine the away game and player
        var awayGame = homeGame;
        var awayCell = jQuery('.scoresheet .AWAYgame' + awayGame);
        if(awayCell.length == 0) return;
        else awayCell = awayCell[0];
        var awayPlayer = parseInt(jQuery(awayCell.parentNode).attr('player'));

        // Determine the matching score
        if(jQuery('.scoresheet .HOMEplayer' + homePlayer).val() == 'NONE') {
            var theirScore = 8;
        } else if(jQuery('.scoresheet .AWAYplayer' + awayPlayer).val() == 'NONE') {
            var theirScore = 0;
        } else {
            var myScore = parseInt(this.value);
            if(isNaN(myScore)) {
                var theirScore = '';
            } else {
                var theirScore = 15 - myScore;
            }
        }

        // Display the score
        jQuery('.scoresheet .AWAYgame' + awayGame).text(theirScore);

        // Update totals
        my5280.updateTotals();
        break;
    }
}


/**
 * Update totals (including handicaps)
 */
my5280.updateTotals = function()
{
    // Get the home team's total handicap
    var home = 0;
    jQuery('.scoresheet .HOME .teamPlayers .handicap input').each(function() {
        var val = parseInt(this.value);
        if(!isNaN(val)) {
            home += val;
        }
    });
    jQuery('.scoresheet .HOME .teamHandicap').text(home);

    // Get the away team's total handicap
    var away = 0;
    jQuery('.scoresheet .AWAY .teamPlayers .handicap input').each(function() {
        var val = parseInt(this.value);
        if(!isNaN(val)) {
            away += val;
        }
    });
    jQuery('.scoresheet .AWAY .teamHandicap').text(away);

    // Determine per-round handicap for both
    if(home > away) {
        var homeHCP = 0;
        var awayHCP = home - away;
        if(home > 42) {
            awayHCP += home - 42;
        }
    } else {
        var awayHCP = 0;
        var homeHCP = away - home;
        if(away > 42) {
            homeHCP += away - 42;
        }
    }

    // Display home handicaps
    jQuery('.scoresheet .HOME .handicaps .handicap').text(homeHCP);
    jQuery('.scoresheet .HOME .handicaps .totalHandicap').text(
        jQuery('.scoresheet .HOME .handicaps .handicap').length * homeHCP
    );

    // Display away handicaps
    jQuery('.scoresheet .AWAY .handicaps .handicap').text(awayHCP);
    jQuery('.scoresheet .AWAY .handicaps .totalHandicap').text(
        jQuery('.scoresheet .AWAY .handicaps .handicap').length * awayHCP
    );

    // Calculate player, round, and total scores for each team
    for(var iTeam = 0; iTeam < 2; iTeam++) {
        var key = (iTeam == 0 ? 'HOME' : 'AWAY');
        var scoresheet = jQuery('.scoresheet .' + key);

        var playerScores = [];
        var roundScores = [];
        var totalScore = 0;
        scoresheet.find('.score').each(function() {
            var round = parseInt(this.getAttribute('round'));
            var player = parseInt(this.getAttribute('player'));
            var score = parseInt( (iTeam == 0 ? jQuery(this).find('input').val() : jQuery(this).text()) );

            if(playerScores.length < (player + 1)) playerScores[player] = 0;
            if(roundScores.length < (round + 1)) roundScores[round] = 0;

            if(!isNaN(score)) {
                playerScores[player] += score;
                roundScores[round] += score;
                totalScore += score;
            }
        });

        // Display the home team's player totals
        for(var i = 0; i < playerScores.length; i++) {
            scoresheet.find('.player' + i + ' .totalScore').text(playerScores[i]);
        }

        // Display the home team's round totals
        for(var i = 0; i < roundScores.length; i++) {
            var score = roundScores[i];
            var handicap = parseInt(scoresheet.find('.handicap.round' + i).text());
            if(!isNaN(handicap)) {
                score += handicap;
            }
            scoresheet.find('.total.round' + i).text(score);
        }

        // Display the team total
        var handicap = parseInt(scoresheet.find('.totalHandicap').text());
        if(!isNaN(handicap)) totalScore += handicap;
        scoresheet.find('.overallTotal').text(totalScore);
    }
}
