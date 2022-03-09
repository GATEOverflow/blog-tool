var qas_blog_recalc_running = 0;

window.onbeforeunload = function (event) {
    if (qas_blog_recalc_running > 0) {
        event = event || window.event;
        var message = qa_warning_recalc;
        event.returnValue = message;
        return message;
    }
};

function qas_blog_recalc_click(state, elem, value, noteid) {
    if (elem.qas_blog_recalc_running) {
        elem.qa_recalc_stopped = true;

    } else {
        elem.qas_blog_recalc_running = true;
        elem.qa_recalc_stopped = false;
        qas_blog_recalc_running++;

        document.getElementById(noteid).innerHTML = '';
        elem.qa_original_value = elem.value;
        if (value)
            elem.value = value;

        qas_blog_recalc_update(elem, state, noteid);
    }

    return false;
}

function qas_blog_recalc_update(elem, state, noteid) {
    if (state) {
        qas_blog_ajax_post('recalc', {
                state: state,
                code: (elem.form.elements.code_recalc ? elem.form.elements.code_recalc.value : elem.form.elements.code.value)
            },
            function (lines) {
                if (lines[0] == '1') {
                    if (lines[2])
                        document.getElementById(noteid).innerHTML = lines[2];

                    if (elem.qa_recalc_stopped)
                        qas_blog_recalc_cleanup(elem);
                    else
                        qas_blog_recalc_update(elem, lines[1], noteid);

                } else if (lines[0] == '0') {
                    document.getElementById(noteid).innerHTML = lines[2];
                    qas_blog_recalc_cleanup(elem);

                } else {
                    qa_ajax_error();
                    qas_blog_recalc_cleanup(elem);
                }
            }
        );
    }
    else {
        qas_blog_recalc_cleanup(elem);
    }
}

function qas_blog_recalc_cleanup(elem) {
    elem.value = elem.qa_original_value;
    elem.qas_blog_recalc_running = null;
    qas_blog_recalc_running--;
}

function qas_blog_admin_click(target) {
    var p = target.name.split('_');

    var params = {entityid: p[1], action: p[2]};
    params.code = target.form.elements.code.value;

    qas_blog_ajax_post('click_admin', params,
        function (lines) {
            if (lines[0] == '1')
                qa_conceal(document.getElementById('p' + p[1]), 'admin');
            else if (lines[0] == '0') {
                alert(lines[1]);
                qa_hide_waiting(target);
            } else
                qa_ajax_error();
        }
    );

    qa_show_waiting_after(target, false);

    return false;
}