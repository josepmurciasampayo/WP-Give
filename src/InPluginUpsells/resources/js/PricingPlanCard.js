import {useMemo} from 'react';
import {Button} from './Button';
import {Card} from './Card';
import {transformEmphasis, transformStrong} from './utils';

import styles from './PricingPlanCard.module.css'

export const PricingPlanCard = ({name, description, actionText, actionLink, icon, includes, includesHasMore, savingsPercentage}) => {
    const includesLabelId = useMemo(() => `${window.lodash.kebabCase(name)}-includes-label`, [name]);

    return (
        <Card as="article" className={styles.card}>
            <div>
                <img className={styles.icon} src={icon} alt="" />
                <h3 className={styles.title}>{name}</h3>
                <p className={styles.description} dangerouslySetInnerHTML={{__html: transformStrong(description)}} />
                <div className={styles.actionAndSavings}>
                    <Button as="a" href={actionLink} className={styles.button}>{actionText}</Button>
                    <p className={styles.savings}>Save over {savingsPercentage}%</p>
                </div>
            </div>
            <aside aria-labelledby={includesLabelId} className={styles.includes}>
                <h4 id={includesLabelId} className={styles.includesLabel}>
                    <span className="screen-reader-text">{name} </span>Includes
                </h4>
                <ul className={styles.includesList}>
                    {includes.map(include => (
                        <li key={include.feature} className={styles.include}>
                            <img src={include.icon} alt="" className={styles.includeIcon} />
                            <span dangerouslySetInnerHTML={{__html: transformEmphasis(include.feature)}} />
                        </li>
                    ))}
                </ul>
                {includesHasMore && <p className={styles.includesMoreText}>&hellip; and more!</p>}
            </aside>
        </Card>
    );
};
