export type ScreenState = 
  | 'dashboard'
  | 'shipments_list' 
  | 'create_order_info' 
  | 'create_order_product' 
  | 'create_order_review' 
  | 'track_order' 
  | 'shipment' 
  | 'verification' 
  | 'lo_list' 
  | 'checklist' 
  | 'qr_code' 
  | 'rating' 
  | 'done';

export interface ChecklistStep {
  step: number;
  text: string;
  type: 'photo' | 'action' | 'form_spp' | 'form_ukur' | 'konfirmasi_lo';
}
