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
 * Defines some "providers" in the app init process so they can be used by all group choices.
 *
 * @copyright   2019 Dani Palou <dpalou@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

var that = this;

/**
 * Offline provider.
 */

var CHOICEGROUP_TABLE = 'addon_mod_choicegroup_responses';

// Define the database tables.
var siteSchema = {
    name: 'AddonModChoiceGroupOfflineProvider',
    version: 1,
    onlyCurrentSite: true,
    tables: [
        {
            name: CHOICEGROUP_TABLE,
            columns: [
                {
                    name: 'choicegroupid',
                    type: 'INTEGER',
                    primaryKey: true
                },
                {
                    name: 'name',
                    type: 'TEXT'
                },
                {
                    name: 'courseid',
                    type: 'INTEGER'
                },
                {
                    name: 'data',
                    type: 'TEXT'
                },
                {
                    name: 'deleting',
                    type: 'INTEGER'
                },
                {
                    name: 'timecreated',
                    type: 'INTEGER'
                }
            ]
        }
    ]
};

/**
 * Class to handle offline group choices.
 */
function AddonModChoiceGroupOfflineProvider() {
    // Register the schema so the tables are created.
    that.CoreSitesProvider.registerSiteSchema(siteSchema);
}

/**
 * Delete a response stored in DB.
 *
 * @param id Group choice ID to remove.
 * @param siteId Site ID. If not defined, current site.
 * @return Promise resolved if deleted, rejected if failure.
 */
AddonModChoiceGroupOfflineProvider.prototype.deleteResponse = function(id, siteId) {
    return that.CoreSitesProvider.getSite(siteId).then(function(site) {

        return site.getDb().deleteRecords(CHOICEGROUP_TABLE, {choicegroupid: id});
    });
};

/**
 * Get all offline responses.
 *
 * @param siteId Site ID. If not defined, current site.
 * @return Promise resolved with responses.
 */
AddonModChoiceGroupOfflineProvider.prototype.getResponses = function(siteId) {
    return that.CoreSitesProvider.getSite(siteId).then(function(site) {
        return site.getDb().getRecords(CHOICEGROUP_TABLE).then(function(records) {
            // Parse the data of each record.
            records.forEach(function(record) {
                record.data = that.CoreTextUtilsProvider.parseJSON(record.data, []);
            });

            return records;
        });
    });
};

/**
 * Check if there are offline responses to send.
 *
 * @param id Group choice ID.
 * @param siteId Site ID. If not defined, current site.
 * @return Promise resolved with boolean: true if has offline answers, false otherwise.
 */
AddonModChoiceGroupOfflineProvider.prototype.hasResponse = function(id, siteId) {
    return this.getResponse(id, siteId).then(function(response) {
        return !!response.choicegroupid;
    }).catch(function() {
        // No offline data found, return false.
        return false;
    });
};

/**
 * Get an offline response.
 *
 * @param id Group choice ID to get.
 * @param siteId Site ID. If not defined, current site.
 * @return Promise resolved with the stored data.
 */
AddonModChoiceGroupOfflineProvider.prototype.getResponse = function(id, siteId) {
    return that.CoreSitesProvider.getSite(siteId).then((site) => {

        return site.getDb().getRecord(CHOICEGROUP_TABLE, {choicegroupid: id}).then(function(record) {
            // Parse the data.
            record.data = that.CoreTextUtilsProvider.parseJSON(record.data, []);

            return record;
        });
    });
};

/**
 * Store a response to a group choice.
 *
 * @param id Group choice ID.
 * @param name Group choice name.
 * @param courseId Course ID the group choice belongs to.
 * @param data List of selected options.
 * @param deleting If true, the user is deleting responses, if false, submitting.
 * @param siteId Site ID. If not defined, current site.
 * @return Promise resolved when data is successfully stored.
 */
AddonModChoiceGroupOfflineProvider.prototype.saveResponses = function(id, name, courseId, data, deleting, siteId) {
    data = data || [];

    return that.CoreSitesProvider.getSite(siteId).then(function(site) {
        var entry = {
            choicegroupid: id,
            name: name,
            courseid: courseId,
            data: JSON.stringify(data),
            deleting: deleting ? 1 : 0,
            timecreated: Date.now()
        };

        return site.getDb().insertRecord(CHOICEGROUP_TABLE, entry);
    });
};

var choiceGroupOffline = new AddonModChoiceGroupOfflineProvider();

/**
 * Group choice provider.
 */

/**
 * Class to handle group choices.
 */
function AddonModChoiceGroupProvider() {
    // Register the schema so the tables are created.
    that.CoreSitesProvider.registerSiteSchema(siteSchema);
}

/**
 * Delete responses from a group choice.
 *
 * @param id Group choice ID to remove.
 * @param name The group choice name.
 * @param courseId Course ID the group choice belongs to.
 * @param allowOffline Whether to allow storing the data in offline.
 * @param siteId Site ID. If not defined, current site.
 * @return Promise resolved with boolean: true if deleted in server, false if stored in offline. Rejected if failure.
 */
AddonModChoiceGroupProvider.prototype.deleteResponses = function(id, name, courseId, allowOffline, siteId) {
    siteId = siteId || that.CoreSitesProvider.getCurrentSiteId();

    var thisProvider = this;

    // Convenience function to store the delete to be synchronized later.
    var storeOffline = function() {
        return choiceGroupOffline.saveResponses(id, name, courseId, undefined, true, siteId).then(function() {
            return false;
        });
    };

    if (!that.CoreAppProvider.isOnline() && allowOffline) {
        // App is offline, store the action.
        return storeOffline();
    }

    // If there's already some data to be sent to the server, discard it first.
    return choiceGroupOffline.deleteResponse(id, siteId).catch(function() {
        // Nothing was stored already.
    }).then(function() {
        // Now try to delete the responses in the server.
        return thisProvider.deleteResponsesOnline(id, siteId).then(function() {
            return true;
        }).catch(function(error) {
            if (!allowOffline || that.CoreUtilsProvider.isWebServiceError(error)) {
                // The WebService has thrown an error, this means that responses cannot be submitted.
                return Promise.reject(error);
            }

            // Couldn't connect to server, store in offline.
            return storeOffline();
        });
    });
};

/**
 * Delete responses from a group choice. It will fail if offline or cannot connect.
 *
 * @param id Group choice ID to remove.
 * @param siteId Site ID. If not defined, current site.
 * @return Promise resolved if deleted, rejected if failure.
 */
AddonModChoiceGroupProvider.prototype.deleteResponsesOnline = function(id, siteId) {
    return that.CoreSitesProvider.getSite(siteId).then(function(site) {
        var params = {
            choicegroupid: id
        };

        return site.write('mod_choicegroup_delete_choicegroup_responses', params).then(function(response) {

            if (!response || response.status === false) {
                // Couldn't delete the responses. Reject the promise.
                var error = response && response.warnings && response.warnings[0] ?
                        response.warnings[0] : that.CoreUtilsProvider.createFakeWSError('');

                return Promise.reject(error);
            }
        });
    });
};

/**
 * Send the responses to a group choice.
 *
 * @param id Group choice ID to submit.
 * @param name The group choice name.
 * @param courseId Course ID the group choice belongs to.
 * @param data The responses to send.
 * @param allowOffline Whether to allow storing the data in offline.
 * @param siteId Site ID. If not defined, current site.
 * @return Promise resolved with boolean: true if responses sent to server, false if stored in offline. Rejected if failure.
 */
AddonModChoiceGroupProvider.prototype.submitResponses = function(id, name, courseId, data, allowOffline, siteId) {
    siteId = siteId || that.CoreSitesProvider.getCurrentSiteId();

    var thisProvider = this;

    // Convenience function to store the delete to be synchronized later.
    var storeOffline = function() {
        return choiceGroupOffline.saveResponses(id, name, courseId, data, false, siteId).then(function() {
            return false;
        });
    };

    if (!that.CoreAppProvider.isOnline() && allowOffline) {
        // App is offline, store the action.
        return storeOffline();
    }

    // If there's already some data to be sent to the server, discard it first.
    return choiceGroupOffline.deleteResponse(id, siteId).catch(function() {
        // Nothing was stored already.
    }).then(function() {
        // Now try to delete the responses in the server.
        return thisProvider.submitResponsesOnline(id, data, siteId).then(function() {
            return true;
        }).catch(function(error) {
            if (!allowOffline || that.CoreUtilsProvider.isWebServiceError(error)) {
                // The WebService has thrown an error, this means that responses cannot be submitted.
                return Promise.reject(error);
            }

            // Couldn't connect to server, store in offline.
            return storeOffline();
        });
    });
};

/**
 * Send responses from a group choice to Moodle. It will fail if offline or cannot connect.
 *
 * @param id Group choice ID to submit.
 * @param data The responses to send.
 * @param siteId Site ID. If not defined, current site.
 * @return Promise resolved if deleted, rejected if failure.
 */
AddonModChoiceGroupProvider.prototype.submitResponsesOnline = function(id, data, siteId) {
    return that.CoreSitesProvider.getSite(siteId).then(function(site) {
        var params = {
            choicegroupid: id,
            data: data
        };

        return site.write('mod_choicegroup_submit_choicegroup_response', params).then(function(response) {

            if (!response || response.status === false) {
                // Couldn't delete the responses. Reject the promise.
                var error = response && response.warnings && response.warnings[0] ?
                        response.warnings[0] : that.CoreUtilsProvider.createFakeWSError('');

                return Promise.reject(error);
            }
        });
    });
};

var result = {
    choiceGroupProvider: new AddonModChoiceGroupProvider(),
    choiceGroupOffline: choiceGroupOffline
};

result;
