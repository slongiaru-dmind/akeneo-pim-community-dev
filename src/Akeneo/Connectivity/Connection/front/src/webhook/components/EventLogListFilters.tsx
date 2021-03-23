import {SectionTitle} from 'akeneo-design-system';
import React, {FC} from 'react';
import {useTranslate} from '../../shared/translate';
import styled from 'styled-components';
import {EventLogLevelFilter} from './EventLogLevelFilter';
import {EventSubscriptionLogFilters} from '../model/EventSubscriptionLogFilters';

const StyledSectionTitle = styled(SectionTitle)`
    margin-bottom: 18px;
`;

export const EventLogListFilters: FC<{
    filters: EventSubscriptionLogFilters,
    onChange: (filters: EventSubscriptionLogFilters) => void,
    total?: number,
}> = ({filters, onChange, total}) => {
    const translate = useTranslate();

    return (
        <StyledSectionTitle>
            <SectionTitle.Title>
                {translate('akeneo_connectivity.connection.webhook.event_logs.list.search.title')}
            </SectionTitle.Title>
            <SectionTitle.Spacer/>
            <SectionTitle.Information>
                {undefined !== total ? translate('akeneo_connectivity.connection.webhook.event_logs.list.search.total', {total: total.toString()}, total) : ''}
            </SectionTitle.Information>
            <SectionTitle.Separator/>
            <EventLogLevelFilter
                levels={filters.levels}
                onChange={levels => onChange({...filters, levels: levels})}
            />
        </StyledSectionTitle>
    );
};
