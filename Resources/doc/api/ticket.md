# Oro\Bundle\ZendeskBundle\Entity\Ticket

## ACTIONS  

### get

Retrieve a specific Zendesk ticket record.

{@inheritdoc}

### get_list

Retrieve a collection of Zendesk ticket records.

{@inheritdoc}

### create

Create a new Zendesk ticket record.

The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
   "data": {
      "type": "zendesktickets",
      "attributes": {
         "originId": "1000017",
         "url": "https://company.zendesk.com/api/v2/tickets/1000017.json",
         "subject": "Integer tincidunt",
         "description": "Nam pretium turpis et arcu. Duis arcu tortor, suscipit eget, imperdiet nec, imperdiet iaculis, ipsum.",
         "externalId": "58b008147a5b5",
         "recipient": "1000010_support@company.com",
         "hasIncidents": true,
         "dueAt": "2017-02-01T18:30:52Z"
      },
      "relationships": {
         "collaborators": {
            "data": [
               {
                  "type": "zendeskusers",
                  "id": "13"
               }
            ]
         },        
         "status": {
            "data": {
               "type": "zendeskticketstatuses",
               "id": "pending"
            }
         },
         "priority": {
            "data": {
               "type": "zendeskticketpriorities",
               "id": "high"
            }
         },
         "requester": {
            "data": {
               "type": "zendeskusers",
               "id": "13"
            }
         },
         "submitter": {
            "data": {
               "type": "zendeskusers",
               "id": "13"
            }
         },
         "assignee": {
            "data": {
               "type": "zendeskusers",
               "id": "37"
            }
         },
         "comments": {
            "data": [
               {
                  "type": "zendeskticketcomments",
                  "id": "53"
               },
               {
                  "type": "zendeskticketcomments",
                  "id": "54"
               }
            ]
         },
         "relatedCase": {
            "data": {
               "type": "cases",
               "id": "12"
            }
         }
      }
   }
}
```
{@/request}

### update

Edit a specific Zendesk ticket record.

The updated record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
   "data": {
      "type": "zendesktickets",
      "id": "15",
      "attributes": {
         "originId": "1000017",
         "url": "https://company.zendesk.com/api/v2/tickets/1000017.json",
         "subject": "Integer tincidunt",
         "description": "Nam pretium turpis et arcu. Duis arcu tortor, suscipit eget, imperdiet nec, imperdiet iaculis, ipsum.",
         "externalId": "58b008147a5b5",
         "recipient": "1000010_support@company.com",
         "hasIncidents": true,
         "dueAt": "2017-02-01T18:30:52Z"
      },
      "relationships": {
         "collaborators": {
            "data": [
               {
                  "type": "zendeskusers",
                  "id": "13"
               }
            ]
         },
         "status": {
            "data": {
               "type": "zendeskticketstatuses",
               "id": "pending"
            }
         },
         "priority": {
            "data": {
               "type": "zendeskticketpriorities",
               "id": "high"
            }
         },
         "requester": {
            "data": {
               "type": "zendeskusers",
               "id": "13"
            }
         },
         "submitter": {
            "data": {
               "type": "zendeskusers",
               "id": "13"
            }
         },
         "assignee": {
            "data": {
               "type": "zendeskusers",
               "id": "37"
            }
         },
         "comments": {
            "data": [
               {
                  "type": "zendeskticketcomments",
                  "id": "53"
               },
               {
                  "type": "zendeskticketcomments",
                  "id": "54"
               }
            ]
         },
         "relatedCase": {
            "data": {
               "type": "cases",
               "id": "12"
            }
         }
      }
   }
}
```
{@/request}

### delete

Delete a specific Zendesk ticket record.

{@inheritdoc}

### delete_list

Delete a collection of Zendesk ticket records.

{@inheritdoc}

## SUBRESOURCES

### assignee

#### get_subresource

Retrieve the record of a Zendesk user that a specific Zendesk ticket is assigned to.

#### get_relationship

Retrieve the ID of the a Zendesk user that a specific Zendesk ticket is assigned to.

#### update_relationship

Replace a Zendesk user that a specific Zendesk ticket is assigned to.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "zendeskusers",
    "id": "1"
  }
}
```
{@/request}

### collaborators

#### get_subresource

Retrieve the records of Zendesk users included in a specific Zendesk ticket communications.

#### get_relationship

Retrieve the IDs of Zendesk users included in a specific Zendesk tickets communications.

#### add_relationship

Set Zendesk users that will be included in a specific Zendesk ticket communications.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "zendeskusers",
      "id": "10"
    },
    {
      "type": "zendeskusers",
      "id": "11"
    }
  ]
}
```
{@/request}

#### update_relationship

Replace Zendesk users included in a specific Zendesk ticket communications.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "zendeskusers",
      "id": "10"
    },
    {
      "type": "zendeskusers",
      "id": "11"
    }
  ]
}
```
{@/request}

#### delete_relationship

Remove Zendesk users from being included in a specific  Zendesk ticket communications.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "zendeskusers",
      "id": "1"
    }
  ]
}
```
{@/request}

### comments

#### get_subresource

Retrieve the Zendesk comments made on a specific Zendesk ticket.

#### get_relationship

Retrieve the IDs of the comments made on a specific Zendesk ticket.

#### add_relationship

Set comments made on a specific Zendesk ticket.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "zendeskticketcomments",
      "id": "1"
    },
    {
      "type": "zendeskticketcomments",
      "id": "2"
    }
  ]
}
```
{@/request}

#### update_relationship

Replace comments made on a specific Zendesk ticket.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "zendeskticketcomments",
      "id": "1"
    },
    {
      "type": "zendeskticketcomments",
      "id": "2"
    }
  ]
}
```
{@/request}

#### delete_relationship

Remove comments made on a specific Zendesk ticket.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "zendeskticketcomments",
      "id": "1"
    }
  ]
}
```
{@/request}

### priority

#### get_subresource

Retrieve the Zendesk priority record configured for a specific Zendesk ticket.

#### get_relationship

Retrieve the ID of the Zendesk priority configured for a specific Zendesk ticket.

#### update_relationship

Replace the Zendesk priority configured for a specific Zendesk ticket.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "zendeskticketpriorities",
    "id": "high"
  }
}
```
{@/request}

### problem

#### get_subresource

Retrieve the Zendesk problem record configured for a specific Zendesk ticket.

#### get_relationship

Retrieve the ID of the Zendesk ticket where the problem that led to a creation of a specific Zendesk ticket is described.

#### update_relationship

Replace the Zendesk ticket where the problem that led to a creation of a specific Zendesk ticket is described.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "zendesktickets",
    "id": "2"
  }
}
```
{@/request}

### relatedCase

#### get_subresource

Retrieve the Oro case that is associated with a specific Zendesk ticket.

#### get_relationship

Retrieve the ID of a Oro case that is associated with a specific Zendesk ticket.

#### update_relationship

Replace the Oro case that is associated with a specific Zendesk ticket.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "cases",
    "id": "20"
  }
}
```
{@/request}

### requester

#### get_subresource

Retrieve the Zendesk user who initiated a specific Zendesk ticket creation.

#### get_relationship

Retrieve the ID of a Zendesk user who initiated a specific Zendesk ticket creation.

#### update_relationship

Replace a Zendesk who initiated a specific Zendesk ticket creation.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "zendeskusers",
    "id": "1"
  }
}
```
{@/request}

### status

#### get_subresource

Retrieve the Zendesk ticket status record configured for a specific Zendesk ticket record.

#### get_relationship

Retrieve the ID of the Zendesk status configured for a specific Zendesk ticket.

#### update_relationship

Replace the Zendesk status configured for a specific Zendesk ticket.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "zendeskticketstatuses",
    "id": "solved"
  }
}
```
{@/request}

### submitter

#### get_subresource

Retrieve the record of a Zendesk user  who created a ticket.

#### get_relationship

Retrieve the ID of a Zendesk user who created a specific Zendesk ticket.

#### update_relationship

Replace a Zendesk user who created a specific Zendesk ticket.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "zendeskusers",
    "id": "1"
  }
}
```
{@/request}

### ticketType

#### get_subresource

Retrieve the record of a Zendesk ticket type configured for a specific Zendesk ticket.

#### get_relationship

Retrieve the ID a Zendesk ticket type configured for a specific Zendesk ticket.

#### update_relationship

Replace the Zendesk ticket type configured for a specific Zendesk ticket.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "zendesktickettypes",
    "id": "incident"
  }
}
```
{@/request}

### channel

#### get_subresource

Retrieve an integration channel via which information about the Zendesk ticket is received.

#### get_relationship

Retrieve the ID of an integration channel via which information about the Zendesk ticket is received.

#### update_relationship

Replace an integration channel via which information about the Zendesk ticket is received.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "integrationchannels",
    "id": "1"
  }
}
```
{@/request}


# Oro\Bundle\ZendeskBundle\Entity\TicketType

## ACTIONS

### get

Retrieve a specific Zendesk ticket type record.

{@inheritdoc}

### get_list

Retrieve a collection of Zendesk ticket type records.

{@inheritdoc}


# Oro\Bundle\ZendeskBundle\Entity\TicketPriority

## ACTIONS

### get

Retrieve a specific Zendesk ticket priority record.

{@inheritdoc}

### get_list

Retrieve a collection of Zendesk ticket priority records.

{@inheritdoc}


# Oro\Bundle\ZendeskBundle\Entity\TicketStatus

## ACTIONS

### get

Retrieve a specific Zendesk ticket status record.

{@inheritdoc}

### get_list

Retrieve a collection of Zendesk ticket status records.

{@inheritdoc}
