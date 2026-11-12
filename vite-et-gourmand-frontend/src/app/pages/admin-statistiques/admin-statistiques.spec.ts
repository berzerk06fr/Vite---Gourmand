import { ComponentFixture, TestBed } from '@angular/core/testing';

import { AdminStatistiques } from './admin-statistiques';

describe('AdminStatistiques', () => {
  let component: AdminStatistiques;
  let fixture: ComponentFixture<AdminStatistiques>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [AdminStatistiques],
    }).compileComponents();

    fixture = TestBed.createComponent(AdminStatistiques);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
