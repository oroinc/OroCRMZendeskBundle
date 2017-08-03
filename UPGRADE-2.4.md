UPGRADE FROM 2.3 to 2.4
=======================

- Class `Oro\Bundle\ZendeskBundle\ImportExport\Writer\TicketCommentExportWriter`
   - construction signature was changed, now it takes the next arguments:
       - TicketCommentSyncHelper $ticketCommentHelper
       - ExceptionHandlerInterface $exceptionHandler
   