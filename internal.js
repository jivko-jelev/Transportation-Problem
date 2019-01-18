$('#apply').on('click', function () {
    var transport_table = '<hr class="first-hr">' +
        '<!-- Default unchecked -->\n' +
        '<div class="form-group radio-method">'+
        '<fieldset><legend>Method</legend>'+
        '<label class="radio-inline">\n' +
        '      <input type="radio" name="method" value="minimum" checked>Minimum Element\n' +
        '    </label>\n' +
        '    <label class="radio-inline">\n' +
        '      <input type="radio" name="method" value="northwest">Northwest Corner\n' +
        '    </label>\n' +
        '</fieldset>'+
        '</div>'+
    '                   <div class="form-group">\n' +
        '                    <label for="manufacturer-values" class="col-sm-2 control-label">Producers</label>\n' +
        '                    <div class="col-sm-10">\n' +
        '                        <input type="text" id="manufacturer-values" name="manufacturer-values" class="form-control" required>\n' +
        '                    </div>\n' +
        '                </div>\n' +
        '                <div class="form-group">\n' +
        '                    <label for="consumer-values" class="col-sm-2 control-label">Consumers</label>\n' +
        '                    <div class="col-sm-10">\n' +
        '                        <input type="text" class="form-control" id="consumer-values" name="consumer-values" >\n' +
        '                    </div>\n' +
        '                </div>\n' +
        '                <p class="legend">Separates each producer and consumer with comma - "220,310,50"</p>\n' +
        '<h3>Matrix of Transport Costs</h3>\n' +
        '<table class="table">\n';
    for (i = 0; i < $('#manufacturers').val(); i++) {
        transport_table += '<tr>';
        for (j = 0; j < $('#consumers').val(); j++) {
            transport_table += '<td><input type="number" name="transport[' + i + '][' + j + ']" class="form-control col-sm-1" required min="1" value="1" autocomplete="off"></td>';
        }
        transport_table += '</tr>';
    }
    transport_table += '</table><button class="btn btn-primary btn-block" type="submit" id="solve" name="solve" disabled="disabled">Solve</button>';
    $('#transport-table').html(transport_table);

    $('#manufacturer-values').on('change', function () {
        var manufacturers_num=$(this).val().split(',').length;
        var consumers_num=$('#consumer-values').val().split(',').length;
        $('#solve').attr('disabled', !(manufacturers_num==$('#manufacturers').val() && $(this).val().trim()!='' &&
            consumers_num==$('#consumers').val() && $('#consumer-values').val().trim()!=''))
    })
    $('#consumer-values').on('change', function () {
        var consumers_num=$(this).val().split(',').length;
        var manufacturers_num=$('#manufacturer-values').val().split(',').length;
        $('#solve').attr('disabled', !(consumers_num==$('#consumers').val() && $(this).val().trim()!='' &&
            manufacturers_num==$('#manufacturers').val() && $('#manufacturer-values').val().trim()!=''))
    })
});

