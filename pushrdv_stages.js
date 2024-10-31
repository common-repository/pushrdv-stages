(function() {
    tinymce.create('tinymce.plugins.pushrdv_stages', {
        /**
         * Initializes the plugin, this will be executed after the plugin has been created.
         * This call is done before the editor instance has finished it's initialization so use the onInit event
         * of the editor instance to intercept that event.
         *
         * @param {tinymce.Editor} ed Editor instance that the plugin is initialized in.
         * @param {string} url Absolute URL to where the plugin is located.
         */
        init : function(ed, url) {
            var values_tab = [];
            jQuery(document).ready(function($) {
                var data = {
                    'action': 'pushrdv_get_agency'
                };
                $.ajax({
                    type: "POST",
                    url: ajaxurl,
                    data: data,
                    dataType: "json",
                    success: function (response) {
                        if(!response.error){
                            if(response.length < 1){
                                values_tab.push({text: 'Aucune agence', value: 0});
                            }else{
                                var first = {text: 'Toutes les agences', value: 'all'};
                                values_tab.push(first);
                                response.forEach(function (element, index) {
                                    var obj = {text: element.name, value: element.shortName};
                                    values_tab.push(obj);
                                });
                            }
                        }else{
                            values_tab.push({text: 'Aucune agence', value: 0});
                        }
                    }
                });
            });
            ed.addButton('pushrdv_stages', {
                title : 'Stages PushRDV',
                cmd : 'makeStages',
                image : url+'/pushrdv2.png'
            });
            ed.addCommand('makeStages', function() {
                ed.windowManager.open({
                    title: 'Stages PushRDV',
                    body: [
                        {
                            type: 'listbox',
                            name: 'agency_shortname',
                            label: 'Sélectionnez une agence',
                            values : values_tab
                        },
                        {
                            type: 'listbox',
                            name: 'limit',
                            label: 'Nombre de stages à afficher',
                            values : [{text: '5', value: 5},{text: '10', value: 10, selected: true},{text: '15', value: 15},{text: '20', value: 20},{text: '25', value: 25},{text: 'Tous', value: 0}]
                        },
                        {
                            type: 'checkbox',
                            name:  'use_colors',
                            label: 'Utiliser les couleurs personnalisées ?',
                            checked: false
                        },
                        {
                            type: 'colorpicker',
                            name: 'main_color',
                            value: '#3467B1',
                            label: 'Sélectionnez la couleur principale'
                        },
                        {
                            type: 'colorpicker',
                            name: 'background',
                            value: '#fff',
                            label: 'Sélectionnez la couleur de fond'
                        }
                    ],
                    onsubmit: function(e) {
                        // Insert content when the window form is submitted
                        if(e.data.agency_shortname != ''){
                            if(e.data.use_colors == true){
                                ed.insertContent('[agencyStages agency_shortname="'+e.data.agency_shortname+'" limit="'+e.data.limit+'" main_color="'+e.data.main_color+'" background="'+e.data.background+'"/]');
                            }else{
                                ed.insertContent('[agencyStages agency_shortname="'+e.data.agency_shortname+'" limit="'+e.data.limit+'"/]');
                            }
                        }

                    }
                });
            });

        },

        /**
         * Creates control instances based in the incomming name. This method is normally not
         * needed since the addButton method of the tinymce.Editor class is a more easy way of adding buttons
         * but you sometimes need to create more complex controls like listboxes, split buttons etc then this
         * method can be used to create those.
         *
         * @param {String} n Name of the control to create.
         * @param {tinymce.ControlManager} cm Control manager to use inorder to create new control.
         * @return {tinymce.ui.Control} New control instance or null if no control was created.
         */
        createControl : function(n, cm) {
            return null;
        },

        /**
         * Returns information about the plugin as a name/value array.
         * The current keys are longname, author, authorurl, infourl and version.
         *
         * @return {Object} Name/value array containing information about the plugin.
         */
        getInfo : function() {
            return {
                longname : 'PushRDV Stages Buttons',
                author : 'Keole',
                authorurl : 'http://www.keole.net/',
                infourl : 'http://www.keole.net/',
                version : "0.1"
            };
        }
    });

    // Register plugin
    tinymce.PluginManager.add( 'pushrdv_stages', tinymce.plugins.pushrdv_stages );
})();