// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This file is part of the Moodle apps support for the choicegroup plugin.
 * Defines the function to be used from the mobile course view template.
 *
 * @copyright   2019 Dani Palou <dpalou@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

var that = this;
var allowOffline = this.CoreConfigConstants.versioncode > 3800; // In 3.8.0 and older plugins couldn't add DB schemas.
var multipleEnrol = this.CONTENT_OTHERDATA.multipleenrollmentspossible;

if (Array.isArray(this.CONTENT_OTHERDATA.data) && this.CONTENT_OTHERDATA.data.length == 0) {
    // When there are no responses we receive an empty array instead of an empty object. Fix it.
    this.CONTENT_OTHERDATA.data = {};
}

var originalData = this.CoreUtilsProvider.clone(this.CONTENT_OTHERDATA.data);

/**
 * Send responses to the site.
 */
this.submitResponses = function() {
    var promise;

    if (!that.CONTENT_OTHERDATA.allowupdate) {
        // Ask the user to confirm.
        that.CoreDomUtilsProvider.showConfirm(that.TranslateService.instant('core.areyousure'));
    } else {
        // No need to confirm.
        promise = Promise.resolve();
    }

    promise.then(function() {
        // Submit the responses now.
        var modal = that.CoreDomUtilsProvider.showModalLoading('core.sending', true);
        var data = that.CoreUtilsProvider.objectToArrayOfObjects(that.CONTENT_OTHERDATA.data, 'name', 'value');

        that.choiceGroupProvider.submitResponses(that.module.instance, that.module.name, that.courseId, data, allowOffline)
                .then(function(online) {

            // Responses have been sent to server or stored to be sent later.
            that.CoreDomUtilsProvider.showToast(that.TranslateService.instant('plugin.mod_choicegroup.choicegroupsaved'));

            if (online) {
                // Check completion since it could be configured to complete once the user answers the choice.
                that.CoreCourseProvider.checkModuleCompletion(that.courseId, that.module.completiondata);

                // Data has been sent, refresh the content.
                return that.refreshContent(true);
            } else {
                // Data stored in offline.
                return that.loadOfflineData();
            }

        }).catch((message) => {
            that.CoreDomUtilsProvider.showErrorModalDefault(message, 'Error submitting responses.', true);
        }).finally(() => {
            modal.dismiss();
        });
    }).catch(() => {
        // User cancelled, ignore.
    });
};

/**
 * Delete the responses. Only if multiple enrol is not allowed.
 */
this.deleteResponses = function() {
    var modal = that.CoreDomUtilsProvider.showModalLoading('core.sending', true);

    that.choiceGroupProvider.deleteResponses(that.module.instance, that.module.name, that.courseId, allowOffline)
            .then(function(online) {

        // Responses have been sent to server or stored to be sent later.
        that.CoreDomUtilsProvider.showToast(that.TranslateService.instant('plugin.mod_choicegroup.choicegroupsaved'));

        if (online) {
            // Data has been sent, refresh the content.
            return that.refreshContent(true);
        } else {
            // Data stored in offline.
            return that.loadOfflineData();
        }

    }).catch((message) => {
        that.CoreDomUtilsProvider.showErrorModalDefault(message, 'Error deleting responses.', true);
    }).finally(() => {
        modal.dismiss();
    });
};

/**
 * Check if the activity has offline data to be sent.
 *
 * @return Promise resolved when done.
 */
this.loadOfflineData = function() {
    // Get the offline response if it exists.
    return that.choiceGroupOffline.getResponse(that.module.instance).then(function(response) {
        that.hasOffline = true;

        if (response.deleting) {
            // Uncheck selected option. Delete is only possible if there is no multiple enrolment.
            delete that.CONTENT_OTHERDATA.data.responses;
            that.showDelete = false;
        } else {
            // Load the offline options into the model.
            that.CONTENT_OTHERDATA.data = {};

            response.data.forEach(function(entry) {
                that.CONTENT_OTHERDATA.data[entry.name] = entry.value;
            });

            that.showDelete = !multipleEnrol; // Show delete if there is offline data and is not multiple enrol.
        }
    }).catch(function() {
        // Offline data not found. Use the original data.
        that.hasOffline = false;
        that.showDelete = that.CONTENT_OTHERDATA.answergiven;
        that.CONTENT_OTHERDATA.data = that.CoreUtilsProvider.clone(originalData);
    });
}

this.moduleName = this.TranslateService.instant('plugin.mod_choicegroup.modulename');

// Check if the group choice has offline data.
this.loadOfflineData().finally(function() {
    that.loaded = true;
});
