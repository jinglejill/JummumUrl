//
//  Document.m
//  JummumUrl
//
//  Created by Thidaporn Kijkamjai on 4/8/2561 BE.
//  Copyright © 2561 Jummum Tech. All rights reserved.
//

#import "Document.h"

@implementation Document
    
- (id)contentsForType:(NSString*)typeName error:(NSError **)errorPtr {
    // Encode your document with an instance of NSData or NSFileWrapper
    return [[NSData alloc] init];
}
    
- (BOOL)loadFromContents:(id)contents ofType:(NSString *)typeName error:(NSError **)errorPtr {
    // Load your document from contents
    return YES;
}

@end
