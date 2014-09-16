window.my5280 = {
    'nextPlayerIndex': 0,
    'nextPaidIndex': 0,
    'nextHandicapIndex': 0,
    'nextScoreIndex': 0
};

my5280.resumeScoreSubmission = function(match)
{
    var storage = jQuery.localStorage;
    var scoresheet = storage.get('my5280scoresheet');
    if(scoresheet && scoresheet.match == match) {
        my5280.startScoreSubmission(match);
    }
}

my5280.startScoreSubmission = function(match, mobile) 
{
    if(typeof(mobile) == 'undefined') mobile = false;

    var load = false;
    var storage = jQuery.localStorage;
    var scoresheet = storage.get('my5280scoresheet');
    if(scoresheet && scoresheet.match == match) {
        load = true;
        mobile = scoresheet.mobile;
    } else {
        // Initialize the scoresheet storage
        var scoresheet = {
            'match': match,
            'mobile': mobile,
            'player': [],
            'paid': [],
            'handicap': [],
            'score': []
        };
        if(mobile) {
            scoresheet.rosterEntered = false;
            scoresheet.currentGames = [null, null];
        }
        storage.set('my5280scoresheet', scoresheet);
    }

    jQuery('.teamPlayers').each(my5280.initTeamPlayersSection);
    jQuery('.HOME .rounds').each(my5280.initRoundSection);

    /*
    jQuery('.scoresheet').append(
        "<br /><div style='text-align: right;'>"
        + "<input type='submit' value='Submit' /></div>"
    );
    */

    // Load existing scoresheet information
    if(load) {
        // Restore data
        for(var key in scoresheet) {
            jQuery('input[name="' + key + '[]"],select[name="' + key + '[]"]').each(function() {
                var index = this.parentNode.getAttribute('submitIndex');
                if(scoresheet[key].length > index) {
                    jQuery(this).val(scoresheet[key][index]);
                }
            });
        }

        // Update handicaps
        my5280.updateHandicaps();
    }

    // Replace the submission link with a cancel link
    jQuery('.startSubmissionLink').html(
        "<a href='javascript:void(0);' onclick='my5280.cancelSubmission(); return false;'>Cancel Submission</a>"
    );

    // Handle mobile mode
    if(mobile) {
        // Hide the header and side nav
        jQuery('#header,#sidebar').hide();

        // Hide the team roster
        jQuery('.scoresheet .teamRoster').hide();

        // Resume where we need to be
        if(scoresheet.currentGames[0]) {
            my5280.displayGame(1, scoresheet.currentGames[0][0], scoresheet.currentGames[0][1]);
            if(scoresheet.currentGames[1]) {
                my5280.displayGame(2, scoresheet.currentGames[1][0], scoresheet.currentGames[1][1]);
            }
        } else {
            jQuery('.scoresheet .teamPlayers .paid').hide();
            jQuery('.scoresheet .teamPlayers').show();
            jQuery('.startMatchLink').html(
                "<a href='javascript:void(0);' onclick='my5280.startMobileEntry(); return false;'>Start First Game</a>"
            );
        }
    }
}

my5280.cancelSubmission = function()
{
    if(window.confirm("Are you SURE you would like to cancel submission?  All"
        + " entered information will be lost.")) {
        var storage = jQuery.localStorage;
        storage.remove('my5280scoresheet');
        document.location.reload();
    }
}

my5280.initTeamPlayersSection = function() 
{
    // Build the list of regular players
    var playerList = '<select name="player[]">'
        + '<option value="NONE">(None)</option>';
    jQuery(this.parentNode).find('.teamRoster .player').each(function() {
        var playerName = jQuery(this).find('.playerName').text().trim();
        var handicap = jQuery(this).find('.handicap').text().trim();
        playerList += '<option value="' + playerName + '" '
            + 'handicap="' + handicap + '">' + playerName + '</option>';
    });
    playerList += '<option value="OTHER">(Other Player)</option>'
        + '</select>';

    // Add the player select
    jQuery(this).find('.playerName').each(function() {
        this.setAttribute('submitIndex', my5280.nextPlayerIndex++);
        jQuery(this).html(playerList);
    });

    // Add the paid input
    jQuery(this).find('.paid').each(function() {
        this.setAttribute('submitIndex', my5280.nextPaidIndex++);
        jQuery(this).html("<input type='text' name='paid[]'"
        + " maxlength='2' size='2' />");
    });

    // Add the handicap input
    jQuery(this).find('.handicap').each(function() {
        this.setAttribute('submitIndex', my5280.nextHandicapIndex++);
        jQuery(this).html(
            "<input type='text' name='handicap[]' maxlength='2' size='2' />"
        );
    });

    // Handle changing of select or input
    jQuery(this).find('input,select').change(my5280.handleChange);
}

my5280.initRoundSection = function()
{
    // Initialize score input
    jQuery(this).find('.score').each(function() {
        this.setAttribute('submitIndex', my5280.nextScoreIndex++);
        jQuery(this).html(
            "<input type='text' name='score[]' maxlength='2' size='2' />"
        );
    });

    // Handle changing of select or input
    jQuery(this).find('input,select').change(my5280.handleChange);
}

my5280.handleChange = function()
{
    // Get information
    var name = this.name.substr(0, this.name.length - 2);
    var value = jQuery(this).val();
    var index = this.parentNode.getAttribute('submitIndex');

    // Store the data in storage
    var storage = jQuery.localStorage;
    var scoresheet = storage.get('my5280scoresheet');
    if(scoresheet[name].length < index) {
        for(var i = scoresheet[name].length; i < index; i++) {
            scoresheet[name][i] = '';
        }
    }
    scoresheet[name][index] = value;

    // Perform some specific processing
    switch(name) {
    case 'player':
        var handicap = this.options[this.selectedIndex].getAttribute('handicap');
        if(handicap) {
            jQuery(this.parentNode.parentNode).find('.handicap input').each(function() {
                jQuery(this).val(handicap);
                var myIndex = this.parentNode.getAttribute('submitIndex');
                for(var i = scoresheet['handicap'].length; i < myIndex; i++) {
                    scoresheet['handicap'][i] = '';
                }
                scoresheet['handicap'][myIndex] = handicap;
            });
        }
        break;
    case 'handicap':
        my5280.updateHandicaps();
        break;
    case 'score':
        // Determine the matching score
        var myScore = parseInt(this.value);
        var theirScore = 15 - myScore;
        var theirScores = jQuery('.AWAY .score');
        jQuery(theirScores[index]).html(theirScore);
        break;
    }

    // Save the updated scoresheet
    storage.set('my5280scoresheet', scoresheet);
}

my5280.updateHandicaps = function()
{
    var home = 0;
    jQuery('.scoresheet .HOME .teamPlayers .handicap input').each(function() {
        var val = parseInt(this.value);
        if(!isNaN(val)) {
            home += val;
        }
    });
    jQuery('.scoresheet .HOME .teamHandicap').text(home);

    var away = 0;
    jQuery('.scoresheet .AWAY .teamPlayers .handicap input').each(function() {
        var val = parseInt(this.value);
        if(!isNaN(val)) {
            away += val;
        }
    });
    jQuery('.scoresheet .AWAY .teamHandicap').text(away);

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

    jQuery('.scoresheet .HOME .handicaps .handicap').text(homeHCP);
    jQuery('.scoresheet .HOME .handicaps .totalHandicap').text(
        jQuery('.scoresheet .HOME .handicaps .handicap').length * homeHCP
    );

    jQuery('.scoresheet .AWAY .handicaps .handicap').text(awayHCP);
    jQuery('.scoresheet .AWAY .handicaps .totalHandicap').text(
        jQuery('.scoresheet .AWAY .handicaps .handicap').length * awayHCP
    );
}

my5280.startMobileEntry = function()
{
    // Make sure the team players sections are hidden
    jQuery('.teamPlayers,.teamName,.startMatchLink').hide();

    // Display the mobile submission table
    jQuery('#mobileSubmit').show();

    // Display the 1st and 2nd games
    my5280.displayGame(1, 0, 0);
    my5280.displayGame(2, 0, 1);
}

my5280.displayNextGame = function(index)
{
    var storage = jQuery.localStorage;
    var scoresheet = storage.get('my5280scoresheet');
    var curMatch = scoresheet.currentGames[index];

    curMatch[1]++;
    if(curMatch[1] > 4) {
        curMatch[0]++;
        curMatch[1] = 0;
    }

    // Display the match
    if(curMatch[0] < 5) {
        my5280.displayGame(index, curMatch[0], curMatch[1]);
    } else {
        alert('There are no more games.');
    }
}

my5280.displayPrevGame = function(index)
{
    var storage = jQuery.localStorage;
    var scoresheet = storage.get('my5280scoresheet');
    var curMatch = scoresheet.currentGames[index];

    curMatch[1]--;
    if(curMatch[1] < 0) {
        curMatch[0]--;
        curMatch[1] = 4;
    }

    if(curMatch[0] >= 0) {
        my5280.displayGame(index, curMatch[0], curMatch[1]);
    } else {
        alert('There are no previous games.');
    }
}

my5280.displayGame = function(index, round, game)
{
    var i = (game * 5) + round;

    // Get the player names
    var homePlayer = jQuery('.HOME .teamPlayers select');
    homePlayer = jQuery(homePlayer[game]).val();

    var awayGame = game + round;
    if(awayGame > 4) awayGame -= 5;
    var awayPlayer = jQuery('.AWAY .teamPlayers select');
    awayPlayer = jQuery(awayPlayer[awayGame]).val();

    // Update the form
    jQuery('#match' + index + ' caption').text('Table ' + index + ': '
        + 'Round ' + (round + 1) + ', Game ' + (game + 1));
    jQuery('#match' + index + ' .homePlayer').text(homePlayer);
    jQuery('#match' + index + ' .awayPlayer').text(awayPlayer);

    // Store the round and game
    var storage = jQuery.localStorage;
    var scoresheet = storage.get('my5280scoresheet');
    scoresheet.currentGames[index] = [round, game];
    storage.set('my5280scoresheet', scoresheet);
}
