<?php

namespace App\Http\Requests\Quotation;

/**
 * Editing the existing quotation. Same fields, same authorisation as
 * creating one — kept as its own class, mirroring Store/UpdateLeadRequest,
 * so the route's intent is clear from its type hint even though nothing is
 * overridden today.
 */
class UpdateQuotationRequest extends StoreQuotationRequest {}
