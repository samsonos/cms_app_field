/**
 * Форма редактирования прав для сущности
 */
var fieldForm = function( fieldForm ) {
    fieldButtonsInit();

    s('#btnAddField', fieldForm).tinyboxAjax({
        html: 'html',
        darkBackground: false,
        renderedHandler: function (response, tb) {
            s('.field_edit_form').ajaxSubmit(function (response) {
                if (!response.message) {
                    s('.item-list').html(response.fields);
                    tb._close();
                    fieldButtonsInit();
                } else {
                    s('#Name').css('background-color', 'rgba(255, 0, 0, 0)').parent().css('background-color', 'rgba(255, 0, 0, 0.1)');
                    alert(response.message);
                }
            });
        },
        beforeHandler: function () {
            return true;
        }
    });
};

function fieldButtonsInit() {

    s('a.delete-field-button').each(function(obj) {
        obj.ajaxClick(function(response) {
            s('.item-list').html(response.fields);
            fieldButtonsInit();
        }, function() {
            return confirm("Вы уверены, что хотите безвозвратно удалить связь этого поля со структурой?");
        });
    });

    s('a.edit-field-button').tinyboxAjax({
        html: 'html',
        darkBackground: false,
        renderedHandler: function(response, tb){
            var action = s('.field_edit_form').a('action') + 'edit/';
            s('.field_edit_form').a('action', action);
            s('.field_edit_form').ajaxSubmit(function(response) {
                if (!response.message) {
                    s('.item-list').html(response.fields);
                    tb._close();
                    fieldButtonsInit();
                } else {
                    s('#Name').css('background-color', 'rgba(255, 0, 0, 0)').parent().css('background-color', 'rgba(255, 0, 0, 0.1)');
                    alert(response.message);
                }
            });
        },
        beforeHandler: function() {
            return true;
        }
    });
}
s(document).pageInit(function() {
    initFieldIcons();
});

function initFieldIcons() {
    s('.control.delete').each(function(obj) {
        obj.ajaxClick(function(response) {
            s('.material-content').html(response.table);
            initFieldIcons()
        }, function() {
            return confirm("Вы уверены, что хотите безвозвратно удалить поле?");
        });
    });

    s('.control.edit').each(function(obj) {
        obj.tinyboxAjax({
            html: 'html',
            renderedHandler: function(response, tb){
                s('.field_edit_form').ajaxSubmit(function(response) {
                    s('.material-content').html(response.table);
                    initFieldIcons();
                    tb._close();
                });
            },
            beforeHandler: function() {
                return true;
            }
        });
    });

    s('.field_pager a').each(function(obj) {
        obj.ajaxClick(function(response) {
        s('.material-content').html(response.table);
        s('.field_pager').html('<li>Страница:</li>' + response.pager);
        initFieldIcons();
        });
    });
}
