<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
    <head>
        <meta charset="utf-8"/>


        <title>joomlamailer Installer</title>

        <link rel="stylesheet" type="text/css" href="<?php echo JURI::root(); ?>administrator/components/com_joomailermailchimpintegration/installer/css/bootstrap.min.css" media="screen" />
        <link rel="stylesheet" type="text/css" href="<?php echo JURI::root(); ?>administrator/components/com_joomailermailchimpintegration/installer/css/installer.css" media="screen" />
        <script src="<?php echo JURI::root(); ?>administrator/components/com_joomailermailchimpintegration/installer/js/jquery.1.9.1.min.js" type="text/javascript"></script>
        <script src="<?php echo JURI::root(); ?>administrator/components/com_joomailermailchimpintegration/installer/js/bootstrap.min.js" type="text/javascript"></script>


    </head>
    <body>
        <div class="container">
            <div class="box">
                <h2 class="text-center">
                    <img src="<?php echo JURI::root(); ?>media/com_joomailermailchimpintegration/backend/images/logo.png" alt="" />
                </h2>

                <hr />

                <div class="row bs-wizard text-center" style="border-bottom:0;">
                    <div class="col-xs-2 bs-wizard-step active" id="step1">
                      <div class="text-center bs-wizard-stepnum">Unpacking Files</div>
                      <div class="progress"><div class="progress-bar"></div></div>
                      <a href="#" class="bs-wizard-dot"></a>
                      <!--<div class="bs-wizard-info text-center">Lorem ipsum dolor sit amet.</div>-->
                    </div>

                    <div class="col-xs-2 bs-wizard-step disabled" id="step2">
                      <div class="text-center bs-wizard-stepnum">Updating Database</div>
                      <div class="progress"><div class="progress-bar"></div></div>
                      <a href="#" class="bs-wizard-dot"></a>
                      <!--<div class="bs-wizard-info text-center">Nam mollis tristique erat vel tristique. Aliquam erat volutpat. Mauris et vestibulum nisi. Duis molestie nisl sed scelerisque vestibulum. Nam placerat tristique placerat</div>-->
                    </div>

                    <div class="col-xs-2 bs-wizard-step disabled" id="step3">
                      <div class="text-center bs-wizard-stepnum">Installing Extensions</div>
                      <div class="progress"><div class="progress-bar"></div></div>
                      <a href="#" class="bs-wizard-dot"></a>
                      <!--<div class="bs-wizard-info text-center">Integer semper dolor ac auctor rutrum. Duis porta ipsum vitae mi bibendum bibendum</div>-->
                    </div>

                    <div class="col-xs-2 bs-wizard-step disabled" id="step4">
                      <div class="text-center bs-wizard-stepnum">Cleanup</div>
                      <div class="progress"><div class="progress-bar"></div></div>
                      <a href="#" class="bs-wizard-dot"></a>
                      <!--<div class="bs-wizard-info text-center"> Curabitur mollis magna at blandit vestibulum. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae</div>-->
                    </div>

                    <div class="col-xs-2 bs-wizard-step disabled" id="step5">
                      <div class="text-center bs-wizard-stepnum">Done!</div>
                      <div class="progress"><div class="progress-bar"></div></div>
                      <a href="#" class="bs-wizard-dot"></a>
                      <!--<div class="bs-wizard-info text-center"> Curabitur mollis magna at blandit vestibulum. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae</div>-->
                    </div>
                </div>

                <hr />

                <div id="stepsProgresses"></div>

                <div id="loader">
                    <img src="<?php echo JURI::root(); ?>media/com_joomailermailchimpintegration/backend/images/loader_32.gif" alt="" />
                </div>
            </div>
        </div>

        <div class="modal fade" id="errorReportModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="pull-right">
                            <button type="button" class="btn btn-default cancel" data-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary submit" style="height: 34px;width: 59px;">Send</button>
                        </div>
                        <h4 class="modal-title" id="myModalLabel">Send error report</h4>
                    </div>
                    <div class="modal-body">
                        <form id="errorReportForm">
                            The following report will be sent to errors@joomlamailer.com<br /><br />
                            <div class="box">
                                <div class="pull-right clearfix"><?php echo date('Y-m-d H:i:s');?></div>
                                Installation error on: <?php echo JURI::root(); ?><br /><br />
                                <ul>
                                    <li>joomlamailer: <?php echo $manifest->version . ' (' . $manifest->creationDate . ')';?></li>
                                    <li><?php $jversion = new JVersion(); echo $jversion->getLongVersion();?></li>
                                    <li>PHP: <?php echo phpversion(); ?></li>
                                    <li>Database: <?php echo JFactory::getDBO()->name
                                        . ' (' . JFactory::getDBO()->getConnection()->server_info . ')';?></li>
                                </ul>
                                <label for="contact">Contact (optional)</label><br />
                                <div class="input-group">
                                    <span class="input-group-addon" id="basic-addon1">@</span>
                                    <input type="text" class="form-control" aria-describedby="basic-addon1" id="contact" name="contact" value="<?php echo JFactory::getConfig()->get('mailfrom');?>" />
                                </div>
                                <br />
                                <label for="notes">Additional information (optional)</label><br />
                                <textarea id="notes" name="notes" class="form-control" placeholder="Please enter here any additional information, which might help us to track down the problem."></textarea>
                                <br />
                                The following error(s) occurred during the installation:
                                <br /><br />
                                <pre id="errorReportPrintErrors"></pre>
                            </div>
                            <input type="hidden" name="domain" value="<?php echo JURI::root(); ?>" />
                            <input type="hidden" name="joomlamailer" value="<?php echo $manifest->version
                                . ' (' . $manifest->creationDate . ')';?>" />
                            <input type="hidden" name="Joomla" value="<?php echo $jversion->getLongVersion();?>" />
                            <input type="hidden" name="PHP" value="<?php echo phpversion(); ?>" />
                            <input type="hidden" name="Database" value="<?php echo JFactory::getDBO()->name
                                . ' (' . JFactory::getDBO()->getConnection()->server_info . ')';?>" />
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary submit">Send</button>
                    </div>
                </div>
            </div>
        </div>

        <script type="text/javascript">
        !function($){
            var adminUrl = '<?php echo JURI::base();?>';
            var errors = {};
            var steps = {
                1: {
                    'label': 'Preparing installation'
                },
                2: {
                    'label': 'Creating database tables'
                },
                3: {
                    'label': 'Installing additional extensions'
                },
                4: {
                    'label': 'Removing temporary installation files'
                }
            };

            var TemplateEngine = function(html, options) {
                var re = /<#([^#>]+)?#>/g, reExp = /(^( )?(if|for|else|switch|case|break|{|}))(.*)?/g, code = 'var r=[];\n', cursor = 0;
                var add = function(line, js) {
                    js? (code += line.match(reExp) ? line + '\n' : 'r.push(' + line + ');\n') :
                        (code += line != '' ? 'r.push("' + line.replace(/"/g, '\\"') + '");\n' : '');
                    return add;
                }
                while(match = re.exec(html)) {
                    add(html.slice(cursor, match.index))(match[1], true);
                    cursor = match.index + match[0].length;
                }
                add(html.substr(cursor, html.length - cursor));
                code += 'return r.join("");';
                return new Function(code.replace(/[\r\t\n]/g, '')).apply(options);
            }
            var progressContainerTpl = '<div class="box">\
                    <h3><#this.label#></h3>\
                    <div class="progress progress-striped active" id="progress_step_<#this.step#>>">\
                        <div class="progress-bar" style="width: 0%;"></div>\
                    </div>\
                </div>';

            var installationSteps = {
                1: function() {
                    installerRunning = true;
                    var pc = TemplateEngine(progressContainerTpl, {step: 1, label: steps[1].label});
                    $(pc).prependTo('#stepsProgresses').hide();

                    setTimeout(function() {
                        $('#stepsProgresses .box:first-of-type').slideDown().queue(function() {
                            var _this = $(this);
                            $.ajax({
                                url: adminUrl + 'index.php?option=com_joomailermailchimpintegration&controller=installer&format=raw&task=prepare',
                                beforeSend: function() {
                                    _this.find('.progress-bar').css('width', '33%');
                                }
                            }).done(function() {
                                _this.find('.progress-bar').css('width', '100%');
                                $('#step1').removeClass('active').addClass('complete');

                                setTimeout(function() {
                                    _this.find('.progress').removeClass('progress-striped').find('.progress-bar').addClass('progress-bar-success');
                                    $('#step2').removeClass('disabled').addClass('active');

                                    setTimeout(function() { installationSteps[2](); }, 500);
                                }, 500);
                            });
                        });
                        $('#loader').slideUp();
                    }, 500);
                },
                2: function() {
                    var pc = TemplateEngine(progressContainerTpl, {step: 2, label: steps[2].label});
                    $(pc).prependTo('#stepsProgresses').hide();

                    $('<table class="table">\
                        <tbody>\
                            <tr id="updateDbStep_1">\
                                <td>List subscribers table</td>\
                                <td class="clearfix" style="width: 20px;">\
                                    <div class="pull-right">\
                                        <span class="label label-default">Waiting</span>\
                                    </div>\
                                </td>\
                            </tr>\
                            <tr id="updateDbStep_2">\
                                <td>Custom fields table</td>\
                                <td class="clearfix" style="width: 20px;">\
                                    <div class="pull-right">\
                                        <span class="label label-default">Waiting</span>\
                                    </div>\
                                </td>\
                            </tr>\
                            <tr id="updateDbStep_3">\
                                <td>Campaigns table</td>\
                                <td class="clearfix" style="width: 20px;">\
                                    <div class="pull-right">\
                                        <span class="label label-default">Waiting</span>\
                                    </div>\
                                </td>\
                            </tr>\
                            <tr id="updateDbStep_4">\
                                <td>Registration data table</td>\
                                <td class="clearfix" style="width: 20px;">\
                                    <div class="pull-right">\
                                        <span class="label label-default">Waiting</span>\
                                    </div>\
                                </td>\
                            </tr>\
                            <tr id="updateDbStep_5">\
                                <td>Miscellaneous data table</td>\
                                <td class="clearfix" style="width: 20px;">\
                                    <div class="pull-right">\
                                        <span class="label label-default">Waiting</span>\
                                    </div>\
                                </td>\
                            </tr>\
                            <tr id="updateDbStep_6">\
                                <td>CRM data table</td>\
                                <td class="clearfix" style="width: 20px;">\
                                    <div class="pull-right">\
                                        <span class="label label-default">Waiting</span>\
                                    </div>\
                                </td>\
                            </tr>\
                            <tr id="updateDbStep_7">\
                                <td>CRM users table</td>\
                                <td class="clearfix" style="width: 20px;">\
                                    <div class="pull-right">\
                                        <span class="label label-default">Waiting</span>\
                                    </div>\
                                </td>\
                            </tr>\
                            <tr id="updateDbStep_8">\
                                <td>Update existing tables</td>\
                                <td class="clearfix" style="width: 20px;">\
                                    <div class="pull-right">\
                                        <span class="label label-default">Waiting</span>\
                                    </div>\
                                </td>\
                            </tr>\
                        </tbody>\
                    </table>').appendTo('#stepsProgresses .box:first-of-type');

                    $('#stepsProgresses .box:first-of-type').slideDown().queue(function() {
                        $(this).find('.progress-bar').css('width', '12%');
                    });

                    var d1 = new $.Deferred();
                    updateDb(1, d1);
                    d1.done(function() {
                        $('#step2').removeClass('active').addClass('complete');
                        setTimeout(function() {
                            $('#step3').removeClass('disabled').addClass('active');
                            var status = (errors.updateDb !== undefined) ? 'danger' : 'success';
                            $('#stepsProgresses .box:first-of-type').find('.progress').removeClass('progress-striped')
                                .find('.progress-bar').addClass('progress-bar-' + status);
                            setTimeout(function() { installationSteps[3](); }, 500);
                        }, 500);
                    });
                },
                3: function() {
                    var pc = TemplateEngine(progressContainerTpl, {step: 3, label: steps[3].label});
                    $(pc).prependTo('#stepsProgresses').hide();

                    $('<table class="table">\
                        <tbody>\
                            <tr id="installExtensionsStep_1">\
                                <td>Signup component</td>\
                                <td class="clearfix" style="width: 20px;">\
                                    <div class="pull-right">\
                                        <span class="label label-default">Waiting</span>\
                                    </div>\
                                </td>\
                            </tr>\
                            <tr id="installExtensionsStep_2">\
                                <td>Signup plugin</td>\
                                <td class="clearfix" style="width: 20px;">\
                                    <div class="pull-right">\
                                        <span class="label label-default">Waiting</span>\
                                    </div>\
                                </td>\
                            </tr>\
                            <tr id="installExtensionsStep_3">\
                                <td>Signup module</td>\
                                <td class="clearfix" style="width: 20px;">\
                                    <div class="pull-right">\
                                        <span class="label label-default">Waiting</span>\
                                    </div>\
                                </td>\
                            </tr>\
                            <tr id="installExtensionsStep_4">\
                                <td>Admin statistics module</td>\
                                <td class="clearfix" style="width: 20px;">\
                                    <div class="pull-right">\
                                        <span class="label label-default">Waiting</span>\
                                    </div>\
                                </td>\
                            </tr>\
                            <tr id="installExtensionsStep_5">\
                                <td>Content plugin: Joomla core</td>\
                                <td class="clearfix" style="width: 20px;">\
                                    <div class="pull-right">\
                                        <span class="label label-default">Waiting</span>\
                                    </div>\
                                </td>\
                            </tr>\
                            <tr id="installExtensionsStep_6">\
                                <td>Content plugin: K2</td>\
                                <td class="clearfix" style="width: 20px;">\
                                    <div class="pull-right">\
                                        <span class="label label-default">Waiting</span>\
                                    </div>\
                                </td>\
                            </tr>\
                            <tr id="installExtensionsStep_7">\
                                <td>Content plugin: Virtuemart</td>\
                                <td class="clearfix" style="width: 20px;">\
                                    <div class="pull-right">\
                                        <span class="label label-default">Waiting</span>\
                                    </div>\
                                </td>\
                            </tr>\
                            <tr id="installExtensionsStep_8">\
                                <td>Content plugin: table of contents</td>\
                                <td class="clearfix" style="width: 20px;">\
                                    <div class="pull-right">\
                                        <span class="label label-default">Waiting</span>\
                                    </div>\
                                </td>\
                            </tr>\
                            <tr id="installExtensionsStep_9">\
                                <td>Content plugin: sidebar editor</td>\
                                <td class="clearfix" style="width: 20px;">\
                                    <div class="pull-right">\
                                        <span class="label label-default">Waiting</span>\
                                    </div>\
                                </td>\
                            </tr>\
                            <tr id="installExtensionsStep_10">\
                                <td>Content plugin: Facebook icon</td>\
                                <td class="clearfix" style="width: 20px;">\
                                    <div class="pull-right">\
                                        <span class="label label-default">Waiting</span>\
                                    </div>\
                                </td>\
                            </tr>\
                            <tr id="installExtensionsStep_11">\
                                <td>Content plugin: Twitter icon</td>\
                                <td class="clearfix" style="width: 20px;">\
                                    <div class="pull-right">\
                                        <span class="label label-default">Waiting</span>\
                                    </div>\
                                </td>\
                            </tr>\
                            <tr id="installExtensionsStep_12">\
                                <td>Content plugin: Myspace icon</td>\
                                <td class="clearfix" style="width: 20px;">\
                                    <div class="pull-right">\
                                        <span class="label label-default">Waiting</span>\
                                    </div>\
                                </td>\
                            </tr>\
                            <tr id="installExtensionsStep_13">\
                                <td>Content plugin: JomSocial discussions</td>\
                                <td class="clearfix" style="width: 20px;">\
                                    <div class="pull-right">\
                                        <span class="label label-default">Waiting</span>\
                                    </div>\
                                </td>\
                            </tr>\
                            <tr id="installExtensionsStep_14">\
                                <td>Content plugin: JomSocial profiles</td>\
                                <td class="clearfix" style="width: 20px;">\
                                    <div class="pull-right">\
                                        <span class="label label-default">Waiting</span>\
                                    </div>\
                                </td>\
                            </tr>\
                            <tr id="installExtensionsStep_15">\
                                <td>JomSocial plugin</td>\
                                <td class="clearfix" style="width: 20px;">\
                                    <div class="pull-right">\
                                        <span class="label label-default">Waiting</span>\
                                    </div>\
                                </td>\
                            </tr>\
                            <tr id="installExtensionsStep_16">\
                                <td>Community builder plugin</td>\
                                <td class="clearfix" style="width: 20px;">\
                                    <div class="pull-right">\
                                        <span class="label label-default">Waiting</span>\
                                    </div>\
                                </td>\
                            </tr>\
                        </tbody>\
                    </table>').appendTo('#stepsProgresses .box:first-of-type');

                    $('#stepsProgresses .box:first-of-type').slideDown().queue(function() {
                        $(this).find('.progress-bar').css('width', '6%');
                    });

                    var d2 = new $.Deferred();
                    installExtensions(1, d2);
                    d2.done(function() {
                        setTimeout(function() {
                            $('#step3').removeClass('active').addClass('complete');
                            var status = (errors.installExtensions !== undefined) ? 'danger' : 'success';
                            $('#stepsProgresses .box:first-of-type').find('.progress').removeClass('progress-striped')
                                .find('.progress-bar').addClass('progress-bar-' + status);
                            setTimeout(function() {
                                $('#step4').removeClass('disabled').addClass('active');
                                setTimeout(function() { installationSteps[4](); }, 500);
                            }, 500);
                        }, 2000);
                    });
                },
                4: function() {
                    var pc = TemplateEngine(progressContainerTpl, {step: 4, label: steps[4].label});
                    $(pc).prependTo('#stepsProgresses').hide().slideDown();

                    $.ajax({
                        url: adminUrl + 'index.php?option=com_joomailermailchimpintegration&controller=installer&format=raw&task=cleanup',
                        beforeSend: function() {
                            $('#stepsProgresses .box:first-of-type').find('.progress-bar').css('width', '33%');
                        }
                    }).done(function() {
                        $('#stepsProgresses .box:first-of-type').find('.progress-bar').css('width', '100%');
                        setTimeout(function() {
                            $('#step4').removeClass('active').addClass('complete');
                            $('#stepsProgresses .box:first-of-type').find('.progress').removeClass('progress-striped')
                                .find('.progress-bar').addClass('progress-bar-success');
                            setTimeout(function() {
                                $('#step5').removeClass('disabled').addClass('active');

                                installerRunning = false;

                                if (Object.keys(errors).length == 0) {
                                    $('<div class="alert alert-success" role="alert">\
                                        <span class="glyphicon glyphicon-thumbs-up" aria-hidden="true"></span>\
                                        <b>Congratulations! You have successfully installed the joomlamailer suite.</b>\
                                        Please click this button to proceed to the component:\
                                        <a href="index.php?option=com_joomailermailchimpintegration" class="btn btn-success">\
                                        joomlamailer dashboard</a>\
                                        </div>').prependTo('#stepsProgresses').hide().slideDown();
                                } else {
                                    var errorString = JSON.stringify(errors, undefined, 2);
                                    errorString = errorString.replace(/\\r\\n|\\n/g, '').replace(/ +/g, ' ');
                                    $('#errorReportPrintErrors').html(errorString);

                                    $('<div class="alert alert-warning" role="alert">\
                                        <span class="glyphicon glyphicon-thumbs-down" aria-hidden="true"></span>\
                                        <b>Unfortunately the installation finished with errors.</b><br />\
                                        You should try to install the component once again. If the problem remains please\
                                        click this button to send an error report:\
                                        <span class="btn btn-warning btn-sm" data-toggle="modal" data-target="#errorReportModal">\
                                        Send error report</span>\
                                        <a href="index.php" class="btn btn-info btn-sm pull-right">Back to Joomla!</a>\
                                        </div>').prependTo('#stepsProgresses').hide().slideDown();
                                }
                            }, 500);
                        }, 2000);
                    });
                }
            }

            function updateDb(step, d1) {
                $.ajax({
                    url: adminUrl + 'index.php?option=com_joomailermailchimpintegration&controller=installer&format=raw&task=updatedb',
                    data: {
                        step: step
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.error) {
                            if (errors.updateDb === undefined) {
                                errors.updateDb = [];
                            }
                            if (typeof response.error === 'string') {
                                var errorLabel = 'An error';
                                var errorMsg = response.error;
                            } else {
                                var errorLabel = 'Some errors';
                                var errorMsg = '';
                                for(var i in response.error) {
                                    errorMsg += response.error[i].error + '<br /><br />';
                                }
                            }
                            errors.updateDb.push(response);
                            $('#updateDbStep_' + step + ' .label').removeClass('label-default').addClass('label-danger').text('Error');
                            $('#updateDbStep_' + step + ' td:first-of-type').append('<div class="alert alert-danger" role="alert">'
                                + ' <b>' + errorLabel + ' occurred!</b><br />' + errorMsg + '</div>');
                        } else {
                            $('#updateDbStep_' + step + ' .label').removeClass('label-default').addClass('label-success').text('Done');
                        }
                    }
                }).done(function() {
                    var width = (step < 8) ? 12 * step : 100;
                    $('#stepsProgresses .box:first-of-type').find('.progress-bar').css('width', width + '%');

                    if (step < 8) {
                        setTimeout(function() { updateDb(++step, d1); }, 350);
                    } else {
                        d1.resolve();
                    }
                });
            }

            function installExtensions(step, d2) {
                $.ajax({
                    url: adminUrl + 'index.php?option=com_joomailermailchimpintegration&controller=installer&format=raw&task=installext',
                    data: {
                        step: step
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.error) {
                            if (errors.installExtensions === undefined) {
                                errors.installExtensions = [];
                            }
                            errors.installExtensions.push(response);
                            $('#installExtensionsStep_' + step + ' .label').removeClass('label-default').addClass('label-danger').text('Error');
                            $('#installExtensionsStep_' + step + ' td:first-of-type').append('<div class="alert alert-danger" role="alert">'
                                + ' <b>An error occurred!</b><br />' + response.error
                            + '</div>');
                        } else if (response.notification) {
                            var text = (response.label) ? response.label : 'Warning';
                            $('#installExtensionsStep_' + step + ' .label').removeClass('label-default').addClass('label-warning').text(text);
                            $('#installExtensionsStep_' + step + ' td:first-of-type').append('<div class="alert alert-warning" role="alert">'
                                + response.notification + '</div>');
                        } else {
                            var text = (response.notRequired) ? 'Not required' : 'Done';
                            $('#installExtensionsStep_' + step + ' .label').removeClass('label-default').addClass('label-success').text(text);
                        }
                    }
                }).done(function() {
                    var width = (step < 16) ? 6 * step : 100;
                    $('#stepsProgresses .box:first-of-type').find('.progress-bar').css('width', width + '%');

                    if (step < 16) {
                        setTimeout(function() { installExtensions(++step, d2); }, 350);
                    } else {
                        d2.resolve();
                    }
                });
            }

            var installerRunning = false;

            $(document).ready(function() {
                installationSteps[1]();

                $(document).on('click', '#errorReportModal button.submit', function() {
                    $.ajax({
                        url: adminUrl + 'index.php?option=com_joomailermailchimpintegration&controller=installer&format=raw&task=sendreport',
                        type: 'post',
                        beforeSend: function() {
                            $('#errorReportModal button.submit').html('<img src="<?php echo JURI::root(); ?>media/com_joomailermailchimpintegration/backend/images/loader_16_blue.gif" alt="" />');
                        },
                        data: {
                            formData: $('#errorReportForm').serialize(),
                            errors: JSON.stringify(errors)
                        },
                        success: function(response) {
                            if (response) {
                                alert(response);
                            } else {
                                alert('The report was sent successfully. We will look into it as soon as possible. '
                                    + 'Please note that we can not reply to all error reports. If you have further '
                                    + 'questions please use our forums on joomlamailer.com Thank you!');
                            }
                        }
                    }).done(function() {
                        $('#errorReportModal button.submit').html('Send');
                        $('#errorReportModal').modal('hide');
                    });
                });
                $(document).on('click', '#errorReportModal button.cancel', function() {
                    $('#errorReportModal button.submit').html('Send');
                });
            });

            window.addEventListener('beforeunload', function (e) {
                if (installerRunning !== true) {
                    return undefined;
                }

                var confirmationMessage = 'The installer did not finish!';
                (e || window.event).returnValue = confirmationMessage; //Gecko + IE
                return confirmationMessage; //Gecko + Webkit, Safari, Chrome etc.
            });

        }(window.jQuery);
        </script>
    </body>
</html>
