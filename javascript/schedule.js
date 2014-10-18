var my5280Schedule = {};

my5280Schedule.init = function()
{
    jQuery('select.teamNumber').change(my5280Schedule.teamNumberChanged);
    jQuery('#addWeekButton').click(my5280Schedule.addWeek);
}

my5280Schedule.addWeek = function()
{
    var table = document.getElementById('schedule');
    var rowIndex = table.rows.length;
    var row = table.insertRow(rowIndex);

    var cell = row.insertCell(0);
    cell.className = 'number';
    jQuery(cell).text(rowIndex + 1);

    cell = row.insertCell(1);
    jQuery(cell).html("<input type='text' class='date' name='date[" + (rowIndex + 1) + "]' />");

    var teamCount = jQuery('.teamNumbers tbody tr').length;
    for(var i = 2; i < table.rows[rowIndex - 1].cells.length; i++) {
        cell = row.insertCell(i);
        jQuery(cell).html(
            "<input type='number' name='homeTeam[" + (rowIndex + 1) + "][" + (i - 2) + "]' "
            + "size='2' maxlength='2' min='1' max='" + teamCount + "' /> "
            + "<input type='number' name='awayTeam[" + (rowIndex + 1) + "][" + (i - 2) + "]' "
            + "size='2' maxlength='2' min='1' max='" + teamCount + "' />"
        );
    }
}

my5280Schedule.teamNumberChanged = function()
{
    var newTeam = this.options[this.selectedIndex].value;
    var curSelect = this.name;

    var selects = jQuery('select.teamNumber');
    var selected = {};
    var toChange = null;
    for(var i = 0; i < selects.length; i++) {
        var select = selects[i];

        // Count values
        var value = select.options[select.selectedIndex].value;
        selected[value] = value;

        // Determine the select that should be automatically changed
        if(value == newTeam && select.name != curSelect) {
            toChange = select;
        }
    };

    if(toChange === null) {
        alert(this.name);
    }

    // Change the select that previously had the new team selected
    for(i = 0; i < toChange.options.length; i++) {
        value = toChange.options[i].value;
        if(typeof(selected[value]) == 'undefined') {
            toChange.selectedIndex = i;
            break;
        }
    }
}
