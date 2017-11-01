    var $mode = 'text';
    var $orientation = 'vertical';
    var $useLineInspector = true;
    var $lineHeight = 13;
    var $lineWidth = 1456
    var $scrollTimeout;
    var $diffs = [
    null,
    {d:'Diferença 1: Adicionou 1 linha (3, no segundo arquivo) depois da linha 2 (primeiro arquivo)',l:[2,2]},
    {d:'Diferença 2: Modificou 1 linha (12, primeiro arquivo) to 1 linha (13, no segundo arquivo arquivo)',l:[12,12]},
    {d:'Diferença 3: Adicionou 23 linhas (24 - 46, no segundo arquivo arquivo) depois da linha 21 (primeiro arquivo)',l:[23,45]},
    {d:'Diferença 4: Modificou 1 linha (25, primeiro arquivo) to 1 linha (51, no segundo arquivo arquivo)',l:[50,50]},
    {d:'Diferença 5: Modificou 1 linha (33, primeiro arquivo) to 1 linha (59, no segundo arquivo arquivo)',l:[58,58]},
    {d:'Diferença 6: Deletou 1 linha (43, primeiro arquivo) depois da linha 68 (no segundo arquivo arquivo)',l:[68,68]},
    {d:'Diferença 7: Deletou 6 linhas (58 - 63, primeiro arquivo) depois da linha 77 (no segundo arquivo arquivo)',l:[83,88]}
    ];

    try {
        $(document).ready(function() {
            $(window).bind('load resize', fitToWindow);

            // populateDiffs();
            registerEventHandlers();

            if ($useLineInspector) {
                $('#inspector').show();
                setLineInspector(0);
            }

            $(window).load(function () {
                firstDiff();
            });
        });
    } catch (e) {
        alert('Não foi possível carregar as diferenças corretamente.\n\n Tente re-carregar a pagina.');
    }

    function isIE7() {
        return ($.browser.msie && parseInt($.browser.version) < 8);
    }

    function fitToWindow() {
        var containerWidth = $(window).width() - 23;
        var containerHeight = $(window).height() - 115 - ($useLineInspector ? (2 * $lineHeight + 13) : 0) - (isIE7() ? 7 : 0);

        if ($orientation == 'vertical') {
            var paneWidth = (containerWidth / 2) - 6;
            var paneHeight = containerHeight;
        } else {
            var paneWidth = containerWidth - 6;
            var paneHeight = ((containerHeight - 13) / 2) - 10;
        }

        $('#container').width(containerWidth);
        $('.title').width(paneWidth - 4);
        $('.file').width(paneWidth).height(paneHeight);

        if ($orientation == 'vertical') {
            var titleHeightMax = $('#title1').height();
            if ($('#title2').height() > titleHeightMax)
                titleHeightMax = $('#title2').height();
            $('#title1').height(titleHeightMax);
            $('#title2').height(titleHeightMax);
        }
    }

    function populateDiffs() {
        if ($diffs.length > 1) {
            var lineHeight = $lineHeight;
            if ($.browser.mozilla && $mode == 'binary') {
                lineHeight += 1; // don't know why Firefox behaves differently
            }

            for (var i = 1; i < $diffs.length; i++) {
                $('#currentDiff').append($('<option></option>').attr('value', i).text($diffs[i].d));
                $('.diffs').append($('<div class="diff"></div>')
                                 .attr('title', $diffs[i].d)
                         .css('top', ($diffs[i].l[0] * lineHeight) + 'px')
                         .height((($diffs[i].l[1] - $diffs[i].l[0] + 1) * lineHeight) - 1));
                $('.d' + i).attr('title', $diffs[i].d);
            }

            if ($mode == 'directory') {
                $('.diffs').css('margin-top', (lineHeight - 1) + 'px');
            }

            if (!isIE7()) {
                $('.diffBar').show(); // IE7 and below can't handle the diff bars
            }
            else {
                $('.li, .la, .ld, .lc, .ln, .lo, .lg, .lib, .lab, .ldb, .lcb, .lnb, .lob, .lgb').css({ 'position': 'static' }); // IE7 and below can't handle 'relative'
            }
        } else {
            $('#currentDiff').append($('<option></option>').attr('value', 0).text('(No differences)'));
        }
    }

    function registerEventHandlers() {
        $('#currentDiff').bind('change keyup', function() {
            goto(currentDiff());
        });

        $('#file1').scroll(function (event) {
        	syncScroll(event);
        });
        $('#file2').scroll(function (event) {
        	syncScroll(event);
        });

        $('.file .l').click(function() {
            updateCurrentDiff(this);
            updateLineInspector(this);
        })

        $('#btnFirst').click(firstDiff);
        $('#btnPrevious').click(previousDiff);
        $('#btnNext').click(nextDiff);
        $('#btnLast').click(lastDiff);
    }

    function syncScroll(e) {
    	clearTimeout($scrollTimeout);

    	if (!e) e = window.event;
    	var $source = $(e.target);
    	var $target = ($source.attr('id') == 'file1') ? $('#file2') : $('#file1')

    	$target.off("scroll").scrollTop($source.scrollTop());
    	$target.off("scroll").scrollLeft($source.scrollLeft());

    	$scrollTimeout = setTimeout(function () {
    		$target.scroll(syncScroll);
    	}, 100, e);
    }

    function goto(diff) {
        if (diff > 0 && diff <= $diffs.length) {
            markCurrentDiff(diff);
            setLineInspector($diffs[diff].l[0]);

            if ($('.comparison .diffBar').css('display') != 'none') {
                var block = $('.diff').eq(diff - 1);
                var tableMidpoint = $('.file').height() / 2;

                if (block.height() > $('.pane').height()) {
                    // Scroll to the top of the diff block
                    $('.file').scrollTop(block.position().top - $lineHeight);
                } else {
                    // Scroll so that diff block is centered
                    var midpoint = block.position().top + (block.height() / 2);
                    $('.file').scrollTop(midpoint - tableMidpoint);
                }
            } else {
                // If diff bar's not shown, just scroll first diff line to top
                var scrollPos = $('.d' + diff).first().position().top - $('.l').first().position().top;
                if ($mode == 'directory') {
                    scrollPos += $lineHeight; // to account for header
                }
                $('.file').scrollTop(scrollPos);
            }
        }
    }

    function markCurrentDiff(diff) {
        $('#currentDiff').val(diff);

        $('.diff').removeClass('active');
        $('.comparison .diffs').each(function(i, diffBar) {
            $(diffBar).children().eq(diff - 1).addClass('active');
        });
    }

    function updateCurrentDiff(clickedDiv) {
        var lineNum = $(clickedDiv).parent().children('.l').index($(clickedDiv));
        markCurrentDiff(closestDiffToLine(lineNum));
    }

    function updateLineInspector(clickedDiv) {
        var lineNum = $(clickedDiv).parent().children('.l').index($(clickedDiv));
        setLineInspector(lineNum);
    }

    function setLineInspector(lineNum) {
        var leftLine = $($('#file1 .l').get(lineNum));
        var rightLine = $($('#file2 .l').get(lineNum));

        $('#inspectorLeftContent').html(leftLine.html())
        .attr('class', leftLine.attr('class'));
        $('#inspectorRightContent').html(rightLine.html())
        .attr('class', rightLine.attr('class'));

        if ($('.comparison .num').size() > 0) {
            var leftNum = $($('#file1 .num').get(lineNum));
            var rightNum = $($('#file2 .num').get(lineNum));
            var maxWidth = Math.max(leftNum.width(), rightNum.width());

            $('#inspector .left .num').html(leftNum.html())
            .width(maxWidth);
            $('#inspector .right .num').html(rightNum.html())
            .width(maxWidth);
        } else {
            $('#inspector .num').html('')
            .css('width', 0).css('border', 0).css('padding', 0);
        }
    }

    function closestDiffToLine(num) {
        var closest = {
            diff: null,
            dist: Infinity
        };

        for (var i = 1; i < $diffs.length; i++) {
            if ($diffs[i].l[0] <= num && $diffs[i].l[1] >= num) {
                closest = { diff: i, dist: 0 };
            } else {
                var dist = ($diffs[i].l[0] <= num) ? (num - $diffs[i].l[1]) : ($diffs[i].l[0] - num);
                if (dist <= closest.dist) {
                    closest = { diff: i, dist: dist };
                }
            }
        }

        return closest.diff;
    }

    function currentDiff() {
        return parseInt($('#currentDiff').val());
    }

    function firstDiff() {
        goto(1);
    }

    function previousDiff() {
        var diff = currentDiff();
        if (diff > 1) {
            diff--;
        }
        goto(diff);
    }

    function nextDiff() {
        var diff = currentDiff();
        if (diff < ($diffs.length - 1)) {
            diff++;
        }
        goto(diff);
    }

    function lastDiff() {
        goto($diffs.length - 1);
    }
