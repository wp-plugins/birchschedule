=== BirchPress Scheduler - Appointment Booking & Online Scheduling === 
Contributors: birchpress
Tags: appointment, appointment booking, appointment booking calendar, appointment calendar, appointment scheduling, book appointment, booking calendar, scheduling, booking form, reservation, online scheduling
Requires at least: 3.7
Tested up to: 4.0
Stable tag: 1.9

BirchPress Scheduler is an appointment booking and online scheduling plugin that allows service businesses to take online bookings.

== Description ==

BirchPress Scheduler is an appointment booking and online scheduling plugin that allows service businesses such as spas, yoga studios, contractors and photographers to take online appointment bookings. Merchants can embed a booking form in a page or post on the website, and customers can see service availability and book an appointment online directly. It is an effective tool to manage appointments and staffing schedules.

= Features =
* Customer booking appointments for specific time in a day
* Optimize the booking form for responsive themes
* Support multiple locations
* Support multiple staff and services
* Configure service length, padding time and price
* Assign your employees to different services
* Powerful Admin Panel for booking / appointment management
* Easily embed booking form / calendar into a webpage with shortcode
* Show appointments / schedules in the daily, weekly or monthly view
* Easily add, edit or delete appointment in WP Admin
* Client Management
* Multiple-currency support
* Configure date and time format
* Set the first day of the week
* Track appointment payment history
* Supported languages: English, German, French, Dutch, Norwegian, Danish, Finnish, Spanish, Portuguese, Swedish

= Desired Businesses =
* Client scheduling (Beauty salon, Spa management, Hairdresser, Massage therapist, Acupuncture, Photographers,Personal Trainers, Wellness, Training Institutes, Sightseeing Services, Home Repair, Auto Repair, Tuition, Financial Services)
* Meeting scheduling (Coaching, Phone advice, Consulting, Law Firms, Education)
* Patient scheduling (Doctor, Clinic, Medical)

There are more features supported in [premium editions](http://www.birchpress.com "WordPress Appointment Plugin").

= Extra Features in Premium Editions =
* Auto fill info for existing customers in admin
* Email notifications to staff and clients when your client books an appointment
* Custom email messages with booking / appointment details
* Appointments reschedule and cancellation by customers
* Display staff appointments/bookings in different colors
* Custom booking form based on business needs(add custom fields about appointment or customer info, visible/invisible to admin and customers, required/not required)
* Block busy time and holidays
* Page redirection after booking an appointment
* Returning user booking an appointment with email and password
* WP user integration
* Support minimum time requirement prior to booking
* Set how far in advance an appointment can be booked
* Set the length of time slot for each service
* Set booking availability/schedule for a specific time period
* Calendar Sync (iCal)
* Group booking(accepting multiple clients on the same time slot)
* Add a 'no preference' option to the staff member dropdown list in the frontend booking form
* Support only showing available time slots when booking via WP Admin
* Create new WP roles and capabilities to manage access control for staff members
* Prepayment and PayPal Integration
* Authorize.net Integration
* WooCommerce Integration
* and much more coming soon

Please visit [our website](http://www.birchpress.com "WordPress Appointment Plugin") for more info, or try our online appointment booking [demo](http://www.birchpress.com/demo/ "BirchPress Scheduler Demo") with all features.

== Installation ==

1. Download the plugin file to your computer and unzip it
2. Using an FTP program, or your hosting control panel, upload the unzipped plugin folder to your WordPress installation's wp-content/plugins/ directory.
3. Activate the plugin from the Plugins menu within the WordPress admin.

== Screenshots ==
1. Frontend booking form with a responsive theme
2. Frontend booking form on Laptop
3. Admin calendar view
4. New appointment from admin
5. Edit appointment from admin
6. Multiple locations
7. Staff settings
8. Service settings
9. Client info
10. Currency and other settings

== Frequently Asked Questions ==

= How can I embed the booking form into a post/page? =

To embed the booking form into a post/page, just insert the following shortcode:
[bpscheduler_booking_form]

== Changelog ==

= 1.9 =
* Enhancement: improve the performance of the admin calendar.
* Improvement: Disable the days which are fully booked in the booking form.
* Require PHP 5.3 or above.

= 1.8 =
* Enhancement: Implement some UI changes to improve user experience
* Enhancement: Support up to 10 languages: English, German, French, Dutch, Norwegian, Danish, Finnish, Spanish, Portuguese, Swedish
* Enhancement: Allow the administrator to modify service, location or duration when rescheduling appointments
* Enhancement: Support WordPress 4.0

= 1.7.6 =
* Bug Fix: Conflict with other plugins which use the lessphp lib.
* Enhancement: Make location and service editable when rescheduling at the backend.

= 1.7.4 =
* URGENT BUG FIX: staff and service setting can not be saved correctly.

= 1.7.3 =
* Bug Fix: work around a PHP core related bug that exists in some versions of PHP

= 1.7.2 =
* Bug Fix: hide the additional text box in the booking form which is introduced in the version 1.7.1.

= 1.7.1 =
* Improvement: Implement a new UI of booking in WP Admin
* Improvement: Add Polish translation file
* Improvement: Improve the plugin performance

= 1.6.11 =
* Bug Fix: fix the bug of double booking at same time slot on different locations

= 1.6.10 =
* Improvement: Support selectable provinces of Canada and several other countries.
* Improvement: Support BHD currency.
* Improvement: Support showing 'All' in location dropdown list in Admin calendar.
* Bug Fix: Fix the bug of deleting customers


= 1.6.5 =
* Refactoring code with the new architecture to make the plugin more flexible and extensible.

= 1.6.4 =
* Improvement: Update the German .po file
* Improvement: Support adding shortcode to a pageline section
* Improvement: Delete ‘undefined’ text in front end dropdown list
* Bug Fix: Customer’s name not updated in the calendar

= 1.6.3 =
* Bug Fix: WordPress 3.6 compatibilty bugs
* Bug Fix: WP Admin appointments loading bugs
* Bug Fix: Fix some plugin conflicts
* Enhancement: Change staff member work schedule interval to 5 mins

= 1.6.2 =
* Bug Fix: Compatible with WordPress 3.6
* Enhancement: Add ‘All’ view for locations
* Enhancement: Support adding attributes to shortcode [bp-scheduler-bookingform] to filter the location, service and staff member options
* Bug Fix: translation of  frontend calendar

= 1.5.7 =
* Bug Fix: Fix the corrupt language files.

= 1.5.6 =
* Enhancement: Support Finnish
* Enhancement: Support United Arab Emirates Dirham currency (AED)
* Enhancement: Support adding booking form to the sidebar widget
* Bug Fix: Fix a timezone related bug

= 1.5.5.2 =
* Bug Fix: The location field are shown again when there is only one option. This bug is introduced in 1.5.5.1.

= 1.5.5.1 =
* Bug Fix: Some plugins or themes replace line breaks with <br> tags in the shortcode, which messes up the booking form.

= 1.5.5 =
* Enhancement: Optimize the booking form for responsive themes.
* Enhancement: Support French.
* Enhancement: Hide the service field in the booking form when there is only one option.
* Enhancement: Hide the service provider field in the booking form when there is only one option.

= 1.5.1 =
* Enhancement: Admin can select existing clients when adding appointments.
* Enhancement: Add payment histroy tracking
* Enhancement: Redesign the booking form to improve usability.
* Enhancement: Admin can change the duration of the appointment.
* Enhancement: Hide location when there is only one.

= 1.4.3.3 =
* Update select2 lib to make the booking form compatible to some themes.

= 1.4.3 =
* Update translation files
* Bug Fix: translation related bugs
* Bug Fix: services can not be assigned to staff if services are too many
* Enhancement: try to resolve 'open_basedir restriction in effect' problem

= 1.4.1 =
* Add Hungry support
* Update Spanish and Spanish Chile
* Bug Fix: Fix confliction with other plugins.

= 1.4.0.3 =
* Bug Fix: javascript errors happen when staff select box is empty
* Bug Fix: time options is empty if the end time of work schedule is 11:45pm

= 1.4.0.1 =
* remove some warnings

= 1.4.0 =
* More flexible work schedule settings
* Use select2 library to improve user experiences
* Set first day of the week
* Fix some translation bugs
* Sort services by alphabetical order
* Remove hyphens if the service price type is "don't show"
* Add Turkish lira and South Africa rand support

= 1.3.7 =
* Users with editor role can change business settings
* Fix some translation bugs

= 1.3.6 =
* IMPORTANT: remove unnessary time availability check in the admin calendar

= 1.3.5 =
* CRITICAL: Version 1.3.4 is a bad build. Please update to 1.3.5

= 1.3.4 =
* Show update notices
* Fix a display bug of showing time options in the frontend
* Validate email when saving in the client editing view
* Add waiting message when client booking

= 1.3.3.1 =
* Support date and time format settings
* Bug Fix: confirmation datetime is incorrect
* Support Glider-like themes

= 1.3.2.2 =
* change css rules to be compatible with more themes.

= 1.3.2.1 =
* change the booking form design

= 1.3.2 =
* Improve usability of the booking form with calendar view
* Blocking to select date in the past

= 1.3.1 =
* Compatible with WordPress 3.5 now
* change several css class names in the booking form to avoid conflicting with some themes.

= 1.3.0 =
* change permission level

= 1.2.1 =
* Dutch support (Thanks to Edwin ten Brink)
* Fix the admin menu disappeared bug
* Fix some other bugs

= 1.2.0 =
* BirchSchedule is now BirchPress Scheduler
* Fix a shortcode rendering bug
* Shortcode [birchschedule_bookingform] is deprecated and replaced by [bp-scheduler-bookingform]

= 1.1.1 =
* clean some notices and warnings.

= 1.1.0 =
* Multi-currency support
* Timezone support
* Add translation files to support i18n
* Fix the padding time bug
* Filter staff by locations

= 1.0.3 =
* Fix a deletion bug.

= 1.0.2 =
* Fix the bug that only five staff are shown in the staff list.

= 1.0.1 =
* Fix the bug that pages containing escape booking form shortcode render unneeded scripts.

= 1.0.0 =
* Init release.
