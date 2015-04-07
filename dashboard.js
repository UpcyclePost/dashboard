$(function() {
    new Morris.Area({
        element: 'registrationChart',
        data: registrationChartData,
        xkey: 'period',
        ykeys: ['new','total'],
        labels: ['Subscribers', 'New'],
        fillOpacity: 0.2,
        dateFormat: function (x) { return new Date(x).toDateString(); },
        lineColors: ['#FFFFFF'],
        gridTextColor: '#FFF',
        pointSize: 0,
        hideHover: 'auto',
        hoverCallback: function (index, options, content) {
            var row = options.data[index];
            return '<strong>'+row.period+'</strong><br />Added: ' + row.new + '<br />Total: ' + row.total;
        },
        //events: ['2014-06-06'], //2014-06-06 Mark sent tweet
        events: ['2014-07-29'], //launch 2014-07-29
        eventLineColors: ['#138a72']
    });

    new Morris.Donut({
        element: 'profileChart',
        data: memberChartData,
        fillOpacity: 0.2,
        gridTextColor: '#FFF',
        colors: [
            '#16a085',
            '#138a72'
        ],
        labelColor: '#fff'
    });
});