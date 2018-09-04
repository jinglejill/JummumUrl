select * from (select @rownum := @rownum + 1 AS rank, c.* from (select ifnull(sum(a.Frequency),0) Frequency,ifnull(sum(b.Sales),0) Sales, promotion.PromotionID, promotion.MainBranchID,promotion.Type,promotion.Header,promotion.SubTitle,promotion.TermsConditions,promotion.ImageUrl,promotion.OrderNo,promotion.DiscountMenuID,promotion.VoucherCode from promotion left join promotionbranch ON promotion.PromotionID = promotionbranch.PromotionID left join (select branchID,count(*) as Frequency from receipt where memberID = '$memberID' GROUP BY branchID) a on promotionbranch.BranchID = a.branchID left join (select branchID,SUM(CashAmount+CreditCardAmount+TransferAmount) Sales from receipt where memberID = '$memberID' GROUP BY branchID) b on promotionbranch.BranchID = b.branchID where promotion.status = 1 and date_format(now(),'%Y-%m-%d') between date_format(promotion.startDate,'%Y-%m-%d') and date_format(promotion.endDate,'%Y-%m-%d') and (promotionbranch.BranchID in (select distinct branchID from receipt where memberID = '$memberID' and promotion.promotionID != '$promotionID') or promotion.type = 0) GROUP BY promotion.PromotionID, promotion.MainBranchID,promotion.Type,promotion.Header,promotion.SubTitle,promotion.TermsConditions,promotion.ImageUrl,promotion.OrderNo,promotion.DiscountMenuID,promotion.VoucherCode order by promotion.Type,sum(a.Frequency)desc,sum(b.Sales)desc,promotion.OrderNo) c,(SELECT @rownum := 0) r)d where rank between '$startRow' and '$endRow';
