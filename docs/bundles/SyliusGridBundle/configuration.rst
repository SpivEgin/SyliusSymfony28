Configuration Reference
=======================

Here you will find all configuration options of ``sylius_grid``.

.. code-block:: yaml

    sylius_grid:
        grids:
            app_user: # Your grid name
                driver:
                    name: doctrine/orm
                    options:
                        class: "%app.model.user%"
                sorting:
                    name: asc
                fields:
                    name:
                        type: twig # Type of field
                        label: Name # Label
                        path: . # dot means a whole object
                        sortable: true
                        position: 100
                        options:
                            template: :Grid/Column:_name.html.twig # Only twig column
                            vars:
                                labels: # a template of how does the label look like
                        enabled: true
                filters:
                    name:
                        type: string # Type of filter
                        label: app.ui.name
                        enabled: true
                        template: ~
                        position: 100
                        options: { }
                actions:
                    main:
                        create:
                            type: create
                            label: sylius.ui.create
                            enabled: true
                            icon: ~
                            position: 100
                            options: { }
                    item:
                        update:
                            type: update
                            label: sylius.ui.edit
                            enabled: true
                            icon: ~
                            position: 100
                            options: { }
                        delete:
                            type: delete
                            label: sylius.ui.delete
                            enabled: true
                            icon: ~
                            position: 100
                            options: { }
                        show:
                            type: show
                            label: sylius.ui.show
                            enabled: true
                            icon: ~
                            position: 100
                            options: { }
