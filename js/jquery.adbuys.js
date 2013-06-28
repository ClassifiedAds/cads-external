(function($) {

    var keys = {
        TAB: 9,
        ENTER: 13,
        ESCAPE: 27,
        UP: 38,
        DOWN: 40
    };

    $.fn.adbuyEditor = function() {
        return this.each(function() {
            var $this = $(this);
            bind_events($this);
        });
    };

    function bind_events($container) {
        $container.delegate("div.viewer", "click", viewer_click);
        $container.delegate("input.input,select.input", "blur", input_blur);
        $container.delegate("input.input,select.input", "keydown", input_keypress);
    }

    function viewer_click() {
        $viewer = $(this);
        $data = get_metadata($viewer);
        $editor = $('#e' + $data.id);
        $input = $('#i' + $data.id);

        $viewer.hide();
        $editor.show();
        $input.focus();
    }

    function input_blur() {
        $input = $(this);
        $data = get_metadata($input);
        $viewer = $('#v' + $data.id);
        $editor = $('#e' + $data.id);

        var value = $input.val();
        var defaultValue = $input.attr('defaultValue');

        $viewer.show();
        $editor.hide();

        if(value != defaultValue) {
            $viewer.css({'background-color': 'pink'});
            $.ajax({
                url: "./adbuys_ajat.php",
                type: "GET",
                data: "col=" + $data.col + "&buy_id=" + $data.buy_id + "&data=" + encode(value),
                success: onSuccess,
                error: onError
            });
        }
    }

    function input_keypress(e) {
        $input = $(this);
        $data = get_metadata($input);
        $viewer = $('#v' + $data.id);
        $editor = $('#e' + $data.id);

        switch(e.which) {
            case keys.ESCAPE:
                $input.val('_AJAT_RESET_');
                $input.trigger('blur');
                return false;
            case keys.ENTER:
                $input.trigger('blur');
                return true;
            case keys.UP:
                $input.trigger('blur');
                $row = $viewer.closest('tr').prev();
                $row.find('div.viewer[id*="v' + $data.col + '"]').trigger('click');
                return false;
            case keys.DOWN:
                $input.trigger('blur');
                $row = $viewer.closest('tr').next();
                $row.find('div.viewer[id*="v' + $data.col + '"]').trigger('click');
                return false;
            case keys.TAB:
                if(e.shiftKey) {
                    $column = $viewer.closest('td').prev();
                } else {
                    $column = $viewer.closest('td').next();
                }
                $input.trigger('blur');
                $viewer = $column.find('div.viewer');
                $viewer.trigger('click');
                return false;
            default:
                return true;
        }
    }

    function onSuccess(data, textStatus, jqXHR) {
        data = data.split('\n');
        id = data[0] + data[1]; // col + buy_id
        $viewer = $('#v' + id);
        $input = $('#i' + id);

        $viewer.css({'background-color': ''});
        $viewer.html(data[2]);

        $input.val(data[3]);
        $input.attr('defaultValue', data[3]);
    }

    function onError(jqXHR, message, errorThrown) {
        alert('AJAX Error: ' + message);
    }

    function get_metadata($obj) {
        var id = $obj.attr('id');
        return {
            type: id.substring(0, 1),
            col: id.substring(1, 2),
            buy_id: id.substring(2),
            id: id.substring(1)
        };
    }

    function encode(str) {
        str = escape(str);
        str = str.replace(/\+/g,  '%2B');
        str = str.replace(/%20/g, '+');
        str = str.replace(/\*/g,  '%2A');
        str = str.replace(/\//g,  '%2F');
        str = str.replace(/@/g,   '%40');
        return str;
    }

    function decode(str) {
        str = str.replace('+', ' ');
        str = unescape(str);
        return str;
    }

})( jQuery );
